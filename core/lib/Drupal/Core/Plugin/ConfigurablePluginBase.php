<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\ConfigurableTrait;

/**
 * Base class for plugins that are configurable.
 *
 * Configurable plugins may extend this base class, or implement
 * Drupal\Component\Plugin\ConfigurableTrait directly.  If they implement the
 * trait directly, they are responsible for setting the configuration manually
 * in their constructor.
 */
abstract class ConfigurablePluginBase extends PluginBase implements ConfigurableInterface {
  use ConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

}
