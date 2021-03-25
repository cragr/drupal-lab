<?php

namespace Drupal\TestSite;

use Drupal\Core\Extension\ThemeInstallerInterface;

/**
 * Setup file used by TestSiteInstallTestScript.
 *
 * @see \Drupal\Tests\Scripts\TestSiteApplicationTest
 */
class TestSiteOliveroInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    // @todo Right now we need Standard install for dependencies, figure them out here.
    // $module_installer = \Drupal::service('module_installer');
    // assert($module_installer instanceof ModuleInstallerInterface);
    // $module_installer->install(['block', 'views']);
    $theme_installer = \Drupal::service('theme_installer');
    assert($theme_installer instanceof ThemeInstallerInterface);
    $theme_installer->install(['olivero'], TRUE);
    $system_theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $system_theme_config->set('default', 'olivero')->save();
  }

}
