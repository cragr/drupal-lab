<?php

namespace Drupal\advisory_feed_test;

use GuzzleHttp\Client;

/**
 * Provides a decorator service for the 'http_client' service for testing.
 */
class AdvisoriesTestHttpClient extends Client {

  /**
   * The decorated http_client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $innerClient;

  /**
   * Constructs an AdvisoriesTestHttpClient object.
   */
  public function __construct(Client $client) {
    $this->innerClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function get($uri, array $options = []) {
    $test_end_point = \Drupal::state()->get('advisories_test_endpoint');
    if ($test_end_point && $uri === 'https://updates.drupal.org/psa.json') {
      $uri = $test_end_point;
    }
    return $this->innerClient->get($uri, $options);
  }

  /**
   * Sets the test endpoint for the advisories JSON feed.
   *
   * @param string $test_endpoint
   *   The test endpoint.
   * @param bool $delete_stored_response
   *   Whether to delete stored feed response.
   */
  public static function setTestEndpoint(string $test_endpoint, bool $delete_stored_response = FALSE): void {
    \Drupal::state()->set('advisories_test_endpoint', $test_endpoint);
    if ($delete_stored_response) {
      \Drupal::service('system.sa_fetcher')->deleteStoredResponse();
    }
  }

}
