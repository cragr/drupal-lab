<?php

namespace Drupal\media\OEmbed;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Retrieves and caches information about oEmbed providers.
 */
class ProviderRepository implements ProviderRepositoryInterface {

  /**
   * How long the provider data should be cached, in seconds.
   *
   * @var int
   */
  protected $maxAge;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * URL of a JSON document which contains a database of oEmbed providers.
   *
   * @var string
   */
  protected $providersUrl;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a ProviderRepository instance.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Cache\CacheBackendInterface|\Drupal\Core\KeyValueStore\KeyValueFactoryInterface|null $key_value
   *   (optional) Key value factory.
   * @param int $max_age
   *   (optional) How long the cache data should be kept. Defaults to a week.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface|null $logger_factory
   *   (optional) Logger factory.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, TimeInterface $time, $key_value = NULL, $max_age = 604800, LoggerChannelFactoryInterface $logger_factory = NULL) {
    $this->httpClient = $http_client;
    $this->providersUrl = $config_factory->get('media.settings')->get('oembed_providers_url');
    $this->time = $time;
    $this->maxAge = (int) $max_age;
    if (!($key_value instanceof KeyValueFactoryInterface)) {
      @trigger_error('The keyvalue service should be passed to ProviderRepository::__construct() since 9.2.0. This will be required in Drupal 10.0.0. See https://www.drupal.org/node/3186186', E_USER_DEPRECATED);
      $key_value = \Drupal::service('keyvalue');
    }
    if (is_null($logger_factory)) {
      @trigger_error('The logger.factory service should be passed to ProviderRepository::__construct() since 9.2.0. This will be required in Drupal 10.0.0. See https://www.drupal.org/node/3186186', E_USER_DEPRECATED);
      $logger_factory = \Drupal::service('logger.factory');
    }
    $this->keyValue = $key_value->get('media.oembed');
    $this->logger = $logger_factory->get('media');
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    $current_time = $this->time->getCurrentTime();
    // We use key-value here instead of a cache backend because in the event
    // that oembed.com is down or having issues, using stale data is better than
    // throwing an exception. If we were to use a cache backend, once the data
    // has expired, we have no way to retrieve it in the event of oembed.com
    // being down.
    if (($stored = $this->keyValue->get('providers')) && $stored['expires'] > $current_time) {
      return $stored['data'];
    }

    try {
      $response = $this->httpClient->request('GET', $this->providersUrl);
    }
    catch (RequestException $e) {
      if (isset($stored['data'])) {
        // Use the expired data.
        $this->logger->error('Remote oEmbed providers database returned invalid or empty list, using previous - this may contain out of date information');
        return $stored['data'];
      }
      // We have no previous data and the request failed.
      throw new ProviderException("Could not retrieve the oEmbed provider database from $this->providersUrl", NULL, $e);
    }

    $providers = Json::decode((string) $response->getBody());

    if (!is_array($providers) || empty($providers)) {
      if (isset($stored['data'])) {
        // Use the expired data.
        $this->logger->error('Remote oEmbed providers database returned invalid or empty list, using previous - this may contain out of date information');
        return $stored['data'];
      }
      // We have no previous data and the current data is corrupt.
      throw new ProviderException('Remote oEmbed providers database returned invalid or empty list.');
    }

    $keyed_providers = [];
    foreach ($providers as $provider) {
      try {
        $name = (string) $provider['provider_name'];
        $keyed_providers[$name] = new Provider($provider['provider_name'], $provider['provider_url'], $provider['endpoints']);
      }
      catch (ProviderException $e) {
        // Just skip all the invalid providers.
        // @todo Log the exception message to help with debugging.
      }
    }

    $this->keyValue->set('providers', [
      'data' => $keyed_providers,
      'expires' => $current_time + $this->maxAge,
    ]);
    return $keyed_providers;
  }

  /**
   * {@inheritdoc}
   */
  public function get($provider_name) {
    $providers = $this->getAll();

    if (!isset($providers[$provider_name])) {
      throw new \InvalidArgumentException("Unknown provider '$provider_name'");
    }
    return $providers[$provider_name];
  }

}
