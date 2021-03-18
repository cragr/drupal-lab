<?php

namespace Drupal\system\SecurityAdvisories;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\ProjectInfo;
use Drupal\system\ExtensionVersion;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;

/**
 * Defines a service to get security advisories.
 */
final class SecurityAdvisoriesFetcher {

  /**
   * The key to use to store the advisories feed response.
   */
  protected const ADVISORIES_JSON_EXPIRABLE_KEY = 'advisories_response';

  /**
   * The 'system.advisories' configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The update expirable key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * The extension lists keyed by extension type.
   *
   * @var \Drupal\Core\Extension\ExtensionList[]
   */
  protected $extensionLists = [];

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Whether to use HTTP fallback if HTTPS fails.
   *
   * @var bool
   */
  protected $withHttpFallback;

  /**
   * Constructs a new SecurityAdvisoriesFetcher object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_factory
   *   The expirable key/value factory.
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   The module extension list.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_list
   *   The theme extension list.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profile_list
   *   The profile extension list.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyValueExpirableFactoryInterface $key_value_factory, Client $client, ModuleExtensionList $module_list, ThemeExtensionList $theme_list, ProfileExtensionList $profile_list, LoggerInterface $logger, Settings $settings) {
    $this->config = $config_factory->get('system.advisories');
    $this->keyValueExpirable = $key_value_factory->get('system');
    $this->httpClient = $client;
    $this->extensionLists['module'] = $module_list;
    $this->extensionLists['theme'] = $theme_list;
    $this->extensionLists['profile'] = $profile_list;
    $this->logger = $logger;
    $this->withHttpFallback = $settings->get('update_fetch_with_http_fallback', FALSE);
  }

  /**
   * Gets security advisories that are applicable for the current site.
   *
   * @param bool $allow_http_request
   *   (optional) Whether to allow an HTTP request to fetch the advisories if
   *   there is no stored JSON response. Defaults to TRUE.
   * @param int $timeout
   *   (optional) The timeout in seconds for the request. Defaults to 0 which is
   *   no timeout.
   *
   * @return \Drupal\system\SecurityAdvisories\SecurityAdvisory[]|null
   *   The upstream security advisories, if any. NULL if there was a problem
   *   retrieving the JSON feed, or if there was no stored response and
   *   $allow_http_request was set to FALSE.
   *
   * @throws \GuzzleHttp\Exception\TransferException
   *   Thrown if an error occurs while retrieving security advisories.
   */
  public function getSecurityAdvisories(bool $allow_http_request = TRUE, int $timeout = 0): ?array {
    $advisories = [];

    $json_payload = $this->keyValueExpirable->get(self::ADVISORIES_JSON_EXPIRABLE_KEY);
    if (!is_array($json_payload)) {
      if (!$allow_http_request) {
        return NULL;
      }
      $response = $this->doRequest($timeout, $this->withHttpFallback);
      $interval_seconds = $this->config->get('interval_hours') * 60 * 60;
      $json_payload = Json::decode($response);
      if (is_array($json_payload)) {
        // This value will be deleted if the 'advisories.interval_hours' config
        // is changed to a lower value.
        // @see \Drupal\update\EventSubscriber\ConfigSubscriber::onConfigSave()
        $this->keyValueExpirable->setWithExpire(self::ADVISORIES_JSON_EXPIRABLE_KEY, $json_payload, $interval_seconds);
      }
      else {
        $this->logger->error('The security advisory JSON feed from Drupal.org could not be decoded.');
        return NULL;
      }
    }

    foreach ($json_payload as $json) {
      try {
        $sa = SecurityAdvisory::createFromArray($json);
      }
      catch (\UnexpectedValueException $unexpected_value_exception) {
        // Ignore items in the feed that are in an invalid format. Although
        // this is highly unlikely we should still display the items that are
        // in the correct format.
        watchdog_exception('system', $unexpected_value_exception, 'Invalid security advisory format: ' . Json::encode($json));
        continue;
      }

      if ($this->isApplicable($sa)) {
        $advisories[] = $sa;
      }
    }
    return $advisories;
  }

  /**
   * Deletes the stored JSON feed response, if any.
   */
  public function deleteStoredResponse(): void {
    $this->keyValueExpirable->delete(self::ADVISORIES_JSON_EXPIRABLE_KEY);
  }

  /**
   * Determines if an advisory matches for the existing version of a project.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return bool
   *   TRUE if the security advisory matches the existing version of the
   *   project, or FALSE otherwise.
   */
  protected function matchesExistingVersion(SecurityAdvisory $sa): bool {
    if ($existing_version = $this->getProjectExistingVersion($sa)) {
      $existing_project_version = ExtensionVersion::createFromVersionString($existing_version);
      $insecure_versions = $sa->getInsecureVersions();
      // If a site is running a dev version of Drupal core or an extension we
      // cannot be certain if their version has the security vulnerabilities
      // that make any of the versions in $insecure_versions insecure. Therefore
      // we should err on the side of assuming the site's code does have the
      // security vulnerabilities and show the advisories. This will result in
      // some sites seeing advisories that do not affect their versions but it
      // will make it less likely that sites with the security vulnerabilities
      // will not see the advisories.
      if ($existing_project_version->getVersionExtra() === 'dev') {
        foreach ($insecure_versions as $insecure_version) {
          try {
            $insecure_project_version = ExtensionVersion::createFromVersionString($insecure_version);
          }
          catch (\UnexpectedValueException $exception) {
            // Any invalid versions should not stop the evaluating of
            // valid versions in $insecure_versions. Version numbers that start
            // with core prefix besides '8.x-' are expected in
            // $insecure_versions but will never match and will throw an
            // exception.
            continue;
          }
          if ($existing_project_version->getMajorVersion() === $insecure_project_version->getMajorVersion()) {
            if ($existing_project_version->getMinorVersion() === NULL) {
              // If the dev version doesn't specify a minor version, matching on
              // the major version alone is considered a match.
              return TRUE;
            }
            elseif ($existing_project_version->getMinorVersion() === $insecure_project_version->getMinorVersion()) {
              // If the dev version specifies a minor version, then the insecure
              // version must match on the minor version.
              return TRUE;
            }
          }
        }
      }
      else {
        // If the existing version is not a dev version, then it must match an
        // insecure version exactly.
        return in_array($existing_version, $insecure_versions, TRUE);
      }
    }
    return FALSE;
  }

  /**
   * Gets the project information for a security advisory.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return mixed[]|null
   *   The project information as set in the info.yml file and then processed by
   *   the corresponding extension list if the project exists, or otherwise
   *   NULL.
   */
  protected function getProjectInfo(SecurityAdvisory $sa): ?array {
    $project_info = new ProjectInfo();
    if (!isset($this->extensionLists[$sa->getProjectType()])) {
      return NULL;
    }
    // The project name on the security advisory will not always match the
    // machine name for the extension, so we need to search through all
    // extensions of the expected type to find the matching project.
    foreach ($this->extensionLists[$sa->getProjectType()]->getList() as $extension) {
      if ($project_info->getProjectName($extension) === $sa->getProject()) {
        return $extension->info;
      }
    }
    return NULL;
  }

  /**
   * Gets the existing project version.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return string|null
   *   The project version or NULL if the project does not exist on
   *   the site.
   */
  protected function getProjectExistingVersion(SecurityAdvisory $sa): ?string {
    if ($sa->isCoreAdvisory()) {
      return \Drupal::VERSION;
    }
    $project_info = $this->getProjectInfo($sa);
    return $project_info['version'] ?? NULL;
  }

  /**
   * Determines if a security advisory is applicable for the current site.
   *
   * @param \Drupal\system\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return bool
   *   TRUE if the advisory is applicable for the current site, otherwise FALSE.
   */
  protected function isApplicable(SecurityAdvisory $sa): bool {
    // Only projects that are in the site's codebase can be applicable. Core
    // will always be in the codebase and other projects are in the codebase if
    // ::getProjectInfo() finds a matching extension for the project name.
    if ($sa->isCoreAdvisory() || $this->getProjectInfo($sa)) {
      // PSA advisories are always applicable because they are not dependent on
      // the version of the project that is currently present on the site. Other
      // advisories are only applicable if they match the existing version.
      return $sa->isPsa() || $this->matchesExistingVersion($sa);
    }
    return FALSE;
  }

  /**
   * Applies a GET request with a possible HTTP fallback.
   *
   * This method falls back to HTTP in case there was some certificate
   * problem.
   *
   * @param int $timeout
   *   The timeout in seconds for the request.
   * @param bool $with_http_fallback
   *   Whether the request should fall back to HTTP if HTTPS fails.
   * @param bool $use_https
   *   (optional) Whether to use HTTPS. Defaults to TRUE.
   *
   * @return string
   *   The response.
   */
  protected function doRequest(int $timeout, bool $with_http_fallback, bool $use_https = TRUE): string {
    $url = ($use_https ? 'https' : 'http') . '://updates.drupal.org/psa.json';
    try {
      $response = (string) $this->httpClient->get($url, [RequestOptions::TIMEOUT => $timeout])->getBody();
    }
    catch (TransferException $exception) {
      watchdog_exception('system', $exception);
      if ($with_http_fallback && $use_https) {
        $response = $this->doRequest($timeout, FALSE, FALSE);
      }
      else {
        throw $exception;
      }
    }
    return $response;
  }

}
