<?php

namespace Drupal\Tests\image\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests lazy-load upgrade path.
 *
 * @group image
 */
class ImageLazyLoadUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
    ];
  }

  /**
   * Test lazy-load new setting upgrade path.
   *
   * @see image_post_update_image_loading_priority
   */
  public function testUpdate() {
    $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = $storage->load('node.article.default');
    $component = $view_display->getComponent('field_image');
    $this->assertArrayNotHasKey('image_loading', $component['settings']);
    $this->runUpdates();
    $view_display = $storage->load('node.article.default');
    $component = $view_display->getComponent('field_image');
    $this->assertArrayHasKey('image_loading', $component['settings']);
    $this->assertEquals('lazy', $component['settings']['image_loading']['priority']);
  }

}
