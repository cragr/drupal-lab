<?php

namespace Drupal\Tests\Core\Plugin\Fixtures;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\ConfigurablePluginBase;

/**
 * A fixture to test Configurable Plugins.
 */
class TestConfigurablePlugin extends ConfigurablePluginBase implements DependentPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
