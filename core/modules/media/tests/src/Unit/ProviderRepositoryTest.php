<?php

namespace Drupal\Tests\media\Unit;

use Drupal\Component\Datetime\Time;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\media\OEmbed\Provider;
use Drupal\media\OEmbed\ProviderException;
use Drupal\media\OEmbed\ProviderRepository;
use Drupal\media\OEmbed\ProviderRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Defines a class for testing provider repository functionality.
 *
 * @group media
 */
class ProviderRepositoryTest extends UnitTestCase {

  /**
   * Mocks the http-client.
   *
   * @param array $history
   *   History array.
   * @param int $current_time
   *   Current time.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   Key value store.
   * @param \GuzzleHttp\Psr7\Response $responses
   *   Responses to use.
   *
   * @return \Drupal\media\OEmbed\ProviderRepositoryInterface
   *   Test repository.
   */
  protected function getTestRepository(array &$history, int $current_time, KeyValueFactoryInterface $key_value, Response ...$responses) : ProviderRepositoryInterface {
    // Create a mock and queue the responses.
    $mock = new MockHandler($responses);

    $handler_stack = HandlerStack::create($mock);
    $handler_stack->push(Middleware::history($history));
    $client = new Client(['handler' => $handler_stack]);
    return new ProviderRepository(
      $client,
      $this->getConfigFactoryStub(['media.settings' => ['oembed_providers_url' => 'https://oembed.com/providers.json']]),
      new MockTime($current_time),
      $key_value,
      604800,
      new LoggerChannelFactory()
    );
  }

  /**
   * Tests a successful fetch.
   */
  public function testThatASuccessfulFetchIsStoredInKeyValue() {
    $history = [];
    $time = time();
    $key_value = new KeyValueMemoryFactory();
    $repo = $this->getTestRepository($history, $time, $key_value, new Response(200, [], '[{
          "provider_name": "YouTube",
          "provider_url": "https:\/\/www.youtube.com\/",
          "endpoints": [
              {
                  "schemes": [
                      "https:\/\/*.youtube.com\/watch*",
                      "https:\/\/*.youtube.com\/v\/*",
                      "https:\/\/youtu.be\/*"
                  ],
                  "url": "https:\/\/www.youtube.com\/oembed",
                  "discovery": true
              }
          ]
      }]'));
    $youtube = new Provider('YouTube', 'https://www.youtube.com/', [
      [
        'schemes' => [
          'https://*.youtube.com/watch*',
          'https://*.youtube.com/v/*',
          'https://youtu.be/*',
        ],
        'url' => 'https://www.youtube.com/oembed',
        'discovery' => TRUE,
      ],
    ]);
    $this->assertEquals($youtube, $repo->get('YouTube'));
    $this->assertEquals(['data' => ['YouTube' => $youtube], 'expires' => $time + 604800], $key_value->get('media.oembed')->get('providers'));
  }

  /**
   * Tests that a corrupt response serves from the fallback.
   *
   * @dataProvider providerExpired
   */
  public function testThatACorruptJsonResponseServesFromTheFallback($expired = FALSE) {
    $youtube = new Provider('YouTube', 'https://www.youtube.com/', [
      [
        'schemes' => [
          'https://*.youtube.com/watch*',
          'https://*.youtube.com/v/*',
          'https://youtu.be/*',
        ],
        'url' => 'https://www.youtube.com/oembed',
        'discovery' => TRUE,
      ],
    ]);
    $key_value = new KeyValueMemoryFactory();
    $key_value->get('media.oembed')->set('providers', [
      'data' => [
        'YouTube' => $youtube,
      ],
      'expires' => time() + ($expired ? -86400 : 86400),
    ]);
    $history = [];
    $oembed_repository = $this->getTestRepository(
      $history,
      time(),
      $key_value,
      new Response(200, [], 'this is certainly not json')
    );
    $this->assertEquals($youtube, $oembed_repository->get('YouTube'));
  }

  /**
   * Tests that a request exception serves from the fallback.
   */
  public function testThatARequestExceptionServesFromTheFallback() {
    $youtube = new Provider('YouTube', 'https://www.youtube.com/', [
      [
        'schemes' => [
          'https://*.youtube.com/watch*',
          'https://*.youtube.com/v/*',
          'https://youtu.be/*',
        ],
        'url' => 'https://www.youtube.com/oembed',
        'discovery' => TRUE,
      ],
    ]);
    $key_value = new KeyValueMemoryFactory();
    $key_value->get('media.oembed')->set('providers', [
      'data' => [
        'YouTube' => $youtube,
      ],
      // This is out of date.
      'expires' => time() - 86400,
    ]);
    $history = [];
    $oembed_repository = $this->getTestRepository(
      $history,
      time(),
      $key_value,
      new Response(503, [], "There's a new Sheriff in town")
    );
    $this->assertEquals($youtube, $oembed_repository->get('YouTube'));
  }

  /**
   * Tests that a request exception without primed data is re-thrown.
   */
  public function testThatARequestExceptionWithAnEmptyKeyValueIsReThrown() {
    $history = [];
    $oembed_repository = $this->getTestRepository(
      $history,
      time(),
      new KeyValueMemoryFactory(),
      new Response(418, [], "J'adore une tasse de thé")
    );
    $this->expectException(ProviderException::class);
    $oembed_repository->get('YouTube');
  }

  /**
   * Tests that invalid values without primed data throws an exception.
   */
  public function testThatAnEmptyKeyValueAndInvalidResponseThrowsAnException() {
    $history = [];
    $oembed_repository = $this->getTestRepository(
      $history,
      time(),
      new KeyValueMemoryFactory(),
      new Response(200, [], 'this is not json')
    );
    $this->expectException(ProviderException::class);
    $oembed_repository->get('YouTube');
  }

  /**
   * Tests a successful fetch but with a single corrupt item.
   */
  public function testThatCorrupItemsAreIgnored() {
    $history = [];
    $time = time();
    $key_value = new KeyValueMemoryFactory();
    $repo = $this->getTestRepository($history, $time, $key_value, new Response(200, [], '[{
          "provider_name": "YouTube",
          "provider_url": "https:\/\/www.youtube.com\/",
          "endpoints": [
              {
                  "schemes": [
                      "https:\/\/*.youtube.com\/watch*",
                      "https:\/\/*.youtube.com\/v\/*",
                      "https:\/\/youtu.be\/*"
                  ],
                  "url": "https:\/\/www.youtube.com\/oembed",
                  "discovery": true
              }
          ]
      },{
          "provider_name": "Uncle Daryl\'s videos",
          "provider_url": "not a real url",
          "endpoints": []
      }]'));
    $youtube = new Provider('YouTube', 'https://www.youtube.com/', [
      [
        'schemes' => [
          'https://*.youtube.com/watch*',
          'https://*.youtube.com/v/*',
          'https://youtu.be/*',
        ],
        'url' => 'https://www.youtube.com/oembed',
        'discovery' => TRUE,
      ],
    ]);
    $this->assertEquals($youtube, $repo->get('YouTube'));
    $this->assertEquals(['data' => ['YouTube' => $youtube], 'expires' => $time + 604800], $key_value->get('media.oembed')->get('providers'));
    $this->expectException(\InvalidArgumentException::class);
    $repo->get("Uncle Daryl's videos");
  }

  /**
   * Data provider.
   *
   * @return array
   *   Test cases.
   */
  public function providerExpired() : array {
    return [
      'expired' => [TRUE],
      'fresh' => [],
    ];
  }

}

/**
 * Defines a mock time class with a fixed timestamp.
 */
class MockTime extends Time {

  /**
   * Mock time.
   *
   * @var int
   */
  private $timestamp;

  /**
   * Constructs a new MockTime.
   *
   * @param int $timestamp
   *   Fixed timestamp.
   */
  public function __construct(int $timestamp) {
    $this->timestamp = $timestamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime() {
    return $this->timestamp;
  }

}
