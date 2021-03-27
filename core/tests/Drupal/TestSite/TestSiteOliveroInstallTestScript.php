<?php

namespace Drupal\TestSite;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Menu\MenuLinkManager;

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
    // Install required module for the Olivero front page.
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['block', 'views']);

    // Add a link to the main menu.
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    assert($menu_link_manager instanceof MenuLinkManager);
    $link_properties = ['title' => 'Home', 'route_name' => '<front>', 'menu_name' => 'main'];
    $menu_link_manager->addDefinition('olivero.front_page', $link_properties);

    // Install Olivero and set it as the default theme.
    $theme_installer = \Drupal::service('theme_installer');
    assert($theme_installer instanceof ThemeInstallerInterface);
    $theme_installer->install(['olivero'], TRUE);
    $system_theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $system_theme_config->set('default', 'olivero')->save();
  }

}
