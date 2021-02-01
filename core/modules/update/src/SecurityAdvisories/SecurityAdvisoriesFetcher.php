<?php

namespace Drupal\update\SecurityAdvisories;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Utility\ProjectInfo;
use Drupal\update\ModuleVersion;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Defines a service class to get security advisories.
 */
final class SecurityAdvisoriesFetcher {

  protected const ADVISORIES_FEED_URL = 'https://updates.drupal.org/psa.json';

  const ADVISORIES_RESPONSE_EXPIRABLE_KEY = 'advisories_response';
  /**
   * The 'update.settings' configuration.
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
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyValueExpirableFactoryInterface $key_value_factory, Client $client, ModuleExtensionList $module_list, ThemeExtensionList $theme_list, ProfileExtensionList $profile_list, LoggerInterface $logger) {
    $this->config = $config_factory->get('update.settings');
    $this->keyValueExpirable = $key_value_factory->get('update');
    $this->httpClient = $client;
    $this->extensionLists['module'] = $module_list;
    $this->extensionLists['theme'] = $theme_list;
    $this->extensionLists['profile'] = $profile_list;
    $this->logger = $logger;
  }

  /**
   * Gets security advisories that are applicable for the current site.
   *
   * @return \Drupal\update\SecurityAdvisories\SecurityAdvisory[]
   *   The upstream security advisories.
   *
   * @throws \GuzzleHttp\Exception\TransferException
   *   Thrown if an error occurs while retrieving security advisories.
   */
  public function getSecurityAdvisories(): array {
    $advisories = [];

    $response = $this->keyValueExpirable->get(self::ADVISORIES_RESPONSE_EXPIRABLE_KEY);
    if (!$response) {
      $response = (string) $this->httpClient->get(self::ADVISORIES_FEED_URL)->getBody();
      $interval_seconds = $this->config->get('advisories.interval_hours') * 60 * 60;
      // This value will be deleted if the 'advisories.interval_hours' config is
      // changed to a lower value.
      // @see \Drupal\update\EventSubscriber\ConfigSubscriber::onConfigSave()
      $this->keyValueExpirable->setWithExpire(self::ADVISORIES_RESPONSE_EXPIRABLE_KEY, $response, $interval_seconds);
    }

    $json_payload = Json::decode($response);
    if (is_array($json_payload)) {
      foreach ($json_payload as $json) {
        try {
          $sa = SecurityAdvisory::createFromArray($json);
        }
        catch (\UnexpectedValueException $unexpected_value_exception) {
          // Ignore items in the feed that are in an invalid format. Although
          // this is highly unlikely we should still display the items that are
          // in the correct format.
          watchdog_exception('update', $unexpected_value_exception, 'Invalid security advisory format: ' . Json::encode($json));
          continue;
        }

        $project_type = $sa->getProjectType();
        // Skip projects that are not in the site's codebase. Core will always
        // be present so it will never be skipped. Otherwise projects are
        // skipped if the project type is not a valid extension type or if
        // ::getProjectInfo() does not find a matching extension for the
        // project name.
        if ($project_type !== 'core' && (!isset($this->extensionLists[$project_type]) || !$this->getProjectInfo($sa))) {
          continue;
        }
        // PSA advisories are always displayed because they are not dependent on
        // the version of the project that is currently present on the site.
        // Other advisories are only displayed if they match the existing
        // version.
        if ($sa->isPsa() || $this->matchesExistingVersion($sa)) {
          $advisories[] = $sa;
        }
      }
    }
    else {
      $this->logger->error('The security advisory JSON feed from Drupal.org could not be decoded.');
    }

    return $advisories;
  }

  /**
   * Determines if an advisory matches for the existing version of a project.
   *
   * @param \Drupal\update\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return bool
   *   TRUE if the security advisory matches the existing version of the
   *   project, or FALSE otherwise.
   */
  protected function matchesExistingVersion(SecurityAdvisory $sa): bool {
    if ($existing_version = $this->getProjectExistingVersion($sa)) {
      $existing_project_version = ModuleVersion::createFromVersionString($existing_version);
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
            $insecure_project_version = ModuleVersion::createFromVersionString($insecure_version);
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
   * @param \Drupal\update\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return mixed[]|null
   *   The project information as set in the info.yml file and then processed by
   *   the corresponding extension list if the project exists, or otherwise
   *   NULL.
   */
  protected function getProjectInfo(SecurityAdvisory $sa): ?array {
    $project_info = new ProjectInfo();
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
   * @param \Drupal\update\SecurityAdvisories\SecurityAdvisory $sa
   *   The security advisory.
   *
   * @return string|null
   *   The project version or NULL if the project does not exist on
   *   the site.
   */
  protected function getProjectExistingVersion(SecurityAdvisory $sa): ?string {
    if ($sa->getProjectType() === 'core') {
      return \Drupal::VERSION;
    }
    $project_info = $this->getProjectInfo($sa);
    return $project_info['version'] ?? NULL;
  }

}
