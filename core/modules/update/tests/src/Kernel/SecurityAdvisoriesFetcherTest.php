<?php

namespace Drupal\Tests\update\Kernel;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @coversDefaultClass \Drupal\update\SecurityAdvisories\SecurityAdvisoriesFetcher
 *
 * @group update
 */
class SecurityAdvisoriesFetcherTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'update',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('update');
  }

  /**
   * Tests contrib advisories that should be displayed.
   *
   * @param mixed[] $feed_item
   *   The feed item to test. 'title' and 'link' are omitted from this array
   *   because they do not need to vary between test cases.
   * @param string|null $existing_version
   *   The existing version of the module.
   *
   * @dataProvider providerShowAdvisories
   */
  public function testShowAdvisories(array $feed_item, string $existing_version = NULL): void {
    $this->setProphesizedServices($feed_item, $existing_version);
    $links = $this->getAdvisories();
    $this->assertCount(1, $links);
    self::assertSame('http://example.com', $links[0]->getUrl());
    $this->assertSame('SA title', $links[0]->getTitle());
  }

  /**
   * Data provider for testShowAdvisories().
   */
  public function providerShowAdvisories(): array {
    return [
      'contrib:exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0.0'],
        ],
        'existing_version' => '1.0.0',
      ],
      'contrib:exact:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:not-exact:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '1.0',

      ],
      'contrib:non-matching:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-2.0',
      ],
      'contrib:no-insecure:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => [],
        ],
        'existing_version' => '8.x-2.0',
      ],
      'contrib:no-existing-version:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-2.0'],
        ],
        'existing_version' => '',
      ],
      'contrib:dev:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => [],
        ],
        'existing_version' => '8.x-2.x-dev',
      ],
      'contrib:existing-dev-match-minor:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-1.x-dev',
      ],
      'contrib:existing-dev-match-major-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.1.1'],
        ],
        'existing_version' => '8.x-dev',
      ],
      'contrib:existing-dev-match-minor-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.2.1'],
        ],
        'existing_version' => '8.2.x-dev',
      ],
      'core:exact:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => [\Drupal::VERSION],
        ],
      ],
      'core:exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => [\Drupal::VERSION],
        ],
      ],
      'core:not-exact:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => ['9.1'],
        ],
      ],
      'core:non-matching:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => ['9.0.0'],
        ],
      ],
      'core:no-insecure:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => [],
        ],
      ],
    ];
  }

  /**
   * Tests advisories that should be ignored.
   *
   * @param mixed[] $feed_item
   *   The feed item to test. 'title' and 'link' are omitted from this array
   *   because they do not need to vary between test cases.
   * @param string|null $existing_version
   *   The existing version of the module.
   *
   * @dataProvider providerIgnoreAdvisories
   */
  public function testIgnoreAdvisories(array $feed_item, string $existing_version = NULL): void {
    $this->setProphesizedServices($feed_item, $existing_version);
    $this->assertCount(0, $this->getAdvisories());
  }

  /**
   * Data provider for testIgnoreAdvisories().
   */
  public function providerIgnoreAdvisories(): array {
    return [
      'contrib:not-exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:non-matching:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.1'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:not-exact:non-psa-reversed' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '1.0',
      ],
      'contrib:semver-non-exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0'],
        ],
        'existing_version' => '1.0.0',
      ],
      'contrib:semver-major-match-not-minor:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.1.0'],
        ],
        'existing_version' => '1.0.0',
      ],
      'contrib:semver-major-minor-match-not-patch:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.1.1'],
        ],
        'existing_version' => '1.1.0',
      ],
      'contrib:non-matching-not-exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.1'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:both-extra:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0-extraStringNotSpecial'],
        ],
        'existing_version' => '8.x-1.0-alsoNotSpecialNotMatching',
      ],
      'contrib:semver-7major-match:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['7.x-1.0'],
        ],
        'existing_version' => '1.0.0',
      ],
      'contrib:different-majors:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['7.x-1.0'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:semver-different-majors:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0.0'],
        ],
        'existing_version' => '2.0.0',
      ],
      'contrib:no-version:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.1'],
        ],
        'existing_version' => '',
      ],
      'contrib:insecure-extra:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0-extraStringNotSpecial'],
        ],
        'existing_version' => '8.x-1.0',
      ],
      'contrib:existing-dev-different-minor:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-2.x-dev',
      ],
      'contrib:existing-dev-different-major:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['7.x-1.0'],
        ],
        'existing_version' => '8.x-1.x-dev',
      ],
      'contrib:existing-dev-different-major-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.0.0'],
        ],
        'existing_version' => '9.0.x-dev',
      ],
      'contrib:existing-dev-different-major-no-minor-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.0.0'],
        ],
        'existing_version' => '9.x-dev',
      ],
      'contrib:existing-dev-different-minor-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0.0'],
        ],
        'existing_version' => '1.1.0-dev',
      ],
      'contrib:existing-dev-different-minor-x-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['1.0.0'],
        ],
        'existing_version' => '1.1.x-dev',
      ],
      'contrib:existing-dev-different-8major-semver:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-dev',
      ],
      'contrib:non-existing-project:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'non_existing_project',
          'insecure' => ['8.x-1.0'],
        ],
      ],
      'contrib:non-existing-project:psa' => [
        'feed_item' => [
          'is_psa' => 1,
          'type' => 'module',
          'project' => 'non_existing_project',
          'insecure' => ['8.x-1.0'],
        ],
      ],
      'core:non-matching:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => ['9.0.0'],
        ],
      ],
      'core:non-matching-not-exact:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => ['9.1'],
        ],
      ],
      'core:no-insecure:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'core',
          'project' => 'drupal',
          'insecure' => [],
        ],
      ],
      'contrib:existing-extra:non-psa' => [
        'feed_item' => [
          'is_psa' => 0,
          'type' => 'module',
          'project' => 'the_project',
          'insecure' => ['8.x-1.0'],
        ],
        'existing_version' => '8.x-1.0-extraStringNotSpecial',

      ],
    ];
  }

  /**
   * Sets prophesized 'http_client' and 'extension.list.module' services.
   *
   * @param mixed[] $feed_item
   *   The feed item to test. 'title' and 'link' are omitted from this array
   *   because they do not need to vary between test cases.
   * @param string|null $existing_version
   *   The existing version of the module.
   */
  protected function setProphesizedServices(array $feed_item, string $existing_version = NULL): void {
    // Rebuild the container so that the 'update.sa_fetcher' service will use
    // the new 'http_client' service.
    $this->container->get('kernel')->rebuildContainer();
    $this->container = $this->container->get('kernel')->getContainer();
    $feed_item += [
      'title' => 'SA title',
      'link' => 'http://example.com',
    ];
    $json_string = json_encode([$feed_item]);
    $stream = $this->prophesize(StreamInterface::class);
    $stream->__toString()->willReturn($json_string);
    $response = $this->prophesize(ResponseInterface::class);
    $response->getBody()->willReturn($stream->reveal());
    $client = $this->prophesize(Client::class);
    $client->get('https://updates.drupal.org/psa.json')
      ->willReturn($response->reveal());
    $this->container->set('http_client', $client->reveal());

    if ($existing_version !== NULL) {
      $module_list = $this->prophesize(ModuleExtensionList::class);
      $extension = $this->prophesize(Extension::class)->reveal();
      $extension->info = [
        'project' => 'the_project',
      ];
      if (!empty($existing_version)) {
        $extension->info['version'] = $existing_version;
      }
      $module_list->getList()->willReturn([$extension]);

      $this->container->set('extension.list.module', $module_list->reveal());
    }
  }

  /**
   * Tests that the stored advisories response is deleted on interval decrease.
   */
  public function testIntervalConfigUpdate(): void {
    $feed_item_1 = [
      'is_psa' => 1,
      'type' => 'core',
      'title' => 'Oh no🙀! Advisory 1',
      'project' => 'drupal',
      'insecure' => [\Drupal::VERSION],
    ];
    $feed_item_2 = [
      'is_psa' => 1,
      'type' => 'core',
      'title' => 'Oh no😱! Advisory 2',
      'project' => 'drupal',
      'insecure' => [\Drupal::VERSION],
    ];
    $this->setProphesizedServices($feed_item_1);
    $advisories = $this->getAdvisories();
    $this->assertCount(1, $advisories);
    $this->assertSame($feed_item_1['title'], $advisories[0]->getTitle());

    // Ensure that new feed item is not retrieved because the stored response
    // has not expired.
    $this->setProphesizedServices($feed_item_2);
    $advisories = $this->getAdvisories();
    $this->assertCount(1, $advisories);
    $this->assertSame($feed_item_1['title'], $advisories[0]->getTitle());

    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable('update.settings');
    $interval = $config->get('advisories.interval_hours');
    $config->set('advisories.interval_hours', $interval + 1)->save();

    // Ensure that new feed item is not retrieved when the interval is
    // increased.
    $advisories = $this->getAdvisories();
    $this->assertCount(1, $advisories);
    $this->assertSame($feed_item_1['title'], $advisories[0]->getTitle());

    // Ensure that new feed item is retrieved when the interval is decreased.
    $config->set('advisories.interval_hours', $interval - 1)->save();
    $advisories = $this->getAdvisories();
    $this->assertCount(1, $advisories);
    $this->assertSame($feed_item_2['title'], $advisories[0]->getTitle());
  }

  /**
   * Gets the advisories from the 'update.sa_fetcher' service.
   *
   * @return \Drupal\update\SecurityAdvisories\SecurityAdvisory[]
   *   The advisory links.
   */
  protected function getAdvisories(): array {
    $fetcher = $this->container->get('update.sa_fetcher');
    return $fetcher->getSecurityAdvisories();
  }

}
