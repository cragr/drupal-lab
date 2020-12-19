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
   * Test deprecation of module_load_include() function.
   */
  public function testModuleLoadInclude() {
    $this->expectDeprecation('module_load_include() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead, you should use \Drupal::moduleHandler()->loadInclude(). See https://www.drupal.org/project/drupal/issues/697946');
    $filename = module_load_include('inc', 'module_test', 'module_test.file');
    $this->assertStringEndsWith("module_test.file.inc", $filename);
  }

  /**
   * Test deprecation of module_load_install() function.
   */
  public function testModuleLoadInstall() {
    $this->expectDeprecation('module_load_install() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead, you should use \Drupal::moduleHandler()->loadInstall(). See https://www.drupal.org/project/drupal/issues/697946');
    $filename = module_load_install('node');
    $this->assertStringEndsWith("node.install", $filename);
  }

}
