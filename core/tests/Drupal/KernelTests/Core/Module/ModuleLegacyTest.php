<?php

namespace Drupal\KernelTests\Core\Module;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecations from module.inc file.
 *
 * @group legacy
 */
class ModuleLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['module_test'];

  /**
   * @expectedDeprecation module_load_include() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Instead, you should use \Drupal::moduleHandler()->loadInclude(). See https://www.drupal.org/project/drupal/issues/697946
   */
  public function testModuleLoadInclude() {
    $filename = module_load_include('inc', 'module_test', 'module_test.file');
    $this->assertStringEndsWith("module_test.file.inc", $filename);
  }

}
