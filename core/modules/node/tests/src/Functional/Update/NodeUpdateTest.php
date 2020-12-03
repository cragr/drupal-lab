<?php

namespace Drupal\Tests\node\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests node module updates.
 *
 * @group node
 * @group legacy
 */
class NodeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles[] = __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz';
  }

  /**
   * Tests node_post_update_plural_variants().
   *
   * @see node_post_update_plural_variants()
   */
  public function testPostUpdatePluralVariants() {
    $properties = ['label_singular', 'label_plural', 'label_count'];

    // Check that plural label variant properties are not present before update.
    $node_type = $this->config('node.type.page')->getRawData();
    foreach ($properties as $property) {
      $this->assertArrayNotHasKey($property, $node_type);
    }

    $this->runUpdates();

    // Check that plural label variant properties were added as NULL.
    $node_type = $this->config('node.type.page')->getRawData();
    foreach ($properties as $property) {
      $this->assertArrayHasKey($property, $node_type);
      $this->assertNull($node_type[$property]);
    }
  }

}
