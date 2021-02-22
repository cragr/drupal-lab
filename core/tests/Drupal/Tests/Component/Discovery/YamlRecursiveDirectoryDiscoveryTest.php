<?php

namespace Drupal\Tests\Component\Discovery;

use Drupal\Component\Discovery\YamlDirectoryDiscovery;
use Drupal\Component\FileCache\FileCacheFactory;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * YamlRecursiveDirectoryDiscoveryTest component unit tests.
 *
 * @coversDefaultClass \Drupal\Component\Discovery\YamlRecursiveDirectoryDiscovery
 *
 * @group Discovery
 */
class YamlRecursiveDirectoryDiscoveryTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    // Ensure that FileCacheFactory has a prefix.
    FileCacheFactory::setPrefix('prefix');
  }

  /**
   * Tests YAML directory discovery.
   *
   * @covers ::findAll
   */
  public function testDiscovery() {
    vfsStream::setup('modules', NULL, [
      'test_1' => [
        'subdir1' => [
          'item_1.test.yml' => "id: item1\nname: 'test1 item 1'",
        ],
        'subdir2' => [
          'sub_subdir2' => [
            'item_2.test.yml' => "id: item2\nname: 'test1 item 2'",
          ],
        ],
        'subdir3' => [
          'sub_subdir3' => [
            'sub_sub_subdir3' => [
              'item_3.test.yml' => "id: item3\nname: 'test1 item 3'",
            ],
          ],
        ],
      ],
    ]);

    // Set up the directories to search.
    $directories = [
      // Multiple nested directories with valid items.
      'test_1' => [
        vfsStream::url('modules/test_1/subdir1'),
        vfsStream::url('modules/test_1/subdir2'),
        vfsStream::url('modules/test_1/subdir3'),
      ],
    ];

    $discovery = new YamlDirectoryDiscovery($directories, 'test');
    $data = $discovery->findAll();

    $this->assertSame(['id' => 'item1', 'name' => 'test1 item 1', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/subdir1/item_1.test.yml'], $data['test_1']['item1']);
    $this->assertSame(['id' => 'item2', 'name' => 'test1 item 2', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/subdir2/sub_subdir2/item_2.test.yml'], $data['test_1']['item2']);
    $this->assertSame(['id' => 'item3', 'name' => 'test1 item 3', YamlDirectoryDiscovery::FILE_KEY => 'vfs://modules/test_1/subdir3/sub_subdir3/sub_sub_subdir3/item_3.test.yml'], $data['test_1']['item3']);
    $this->assertCount(3, $data['test_1']);
  }

}
