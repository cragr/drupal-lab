<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests entity bundle label
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityTypeBundleInfo
 * @group Entity
 */
class EntityBundleLabelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * @covers ::getAllBundleInfo
   * @see entity_test_entity_bundle_info_alter()
   */
  public function testLabelAltering(): void {
    $bundle_entity = EntityTestBundle::create([
      'id' => 'bundle_with_alterable_label',
      'label' => 'Alterable label',
    ]);
    $bundle_entity->save();
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('entity_test_with_bundle')['bundle_with_alterable_label'];
    $this->assertSame($bundle_info['label'], $bundle_entity->label());
  }

}
