<?php

namespace Drupal\Tests\file\Functional\Views;

use Drupal\file\Entity\File;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests file usage statistics view.
 *
 * @group file
 */
class FileUsageViewTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['files'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that page with file statistics is working properly.
   */
  public function testFileUsagePage(): void {
    $file = File::create([
      'fid' => 2,
      'uid' => 2,
      'filename' => 'image-test.jpg',
      'uri' => "public://image-test.jpg",
      'filemime' => 'image/jpeg',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->save();
    $account = $this->drupalCreateUser();
    $account->save();

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = $this->container->get('file.usage');
    $file_usage->add($file, 'file', 'user', $account->id());
    $file_usage->add($file, 'file', 'form', 'awesome_form');

    $expected_usage = [
      'file' => [
        'user' => [
          $account->id() => '1',
        ],
        'form' => [
          'awesome_form' => '1',
        ],
      ],
    ];
    // Make sure that usage is correctly stored and API doesn't fails.
    $this->assertEquals($expected_usage, $file_usage->listUsage($file));

    $view = Views::getView('files');
    $view->setDisplay('page_2');
    $this->executeView($view, [$file->id()]);
//    $this->assertCount(2, $view->result);

    $expected_result = [
      [
        'file_usage_module' => 'file',
        'file_usage_type' => 'user',
        'file_usage_id' => $file->id(),
      ],
      [
        'file_usage_module' => 'file',
        'file_usage_type' => 'form',
        'file_usage_id' => 'awesome_form',
      ],
    ];
    $column_map = [
      'file_usage_id' => 'file_usage_id',
      'file_usage_module' => 'file_usage_module',
      'file_usage_type' => 'file_usage_type',
    ];
    $this->assertIdenticalResultset($view, $expected_result, $column_map);
  }

}
