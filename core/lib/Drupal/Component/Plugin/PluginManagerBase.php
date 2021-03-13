<?php

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Base class for plugin managers.
 */
abstract class PluginManagerBase implements PluginManagerInterface {

  use DiscoveryTrait;

  /**
   * The object that discovers plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The object that instantiates plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $factory;

  /**
   * The object that returns the preconfigured plugin instance appropriate for a particular runtime condition.
   *
   * @var \Drupal\Component\Plugin\Mapper\MapperInterface|null
   */
  protected $mapper;

  /**
   * Gets the plugin discovery.
   *
   * @return \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected function getDiscovery() {
    return $this->discovery;
  }

  /**
   * Gets the plugin factory.
   *
   * @return \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected function getFactory() {
    return $this->factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->getDiscovery()->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->getDiscovery()->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // If this PluginManager has fallback capabilities catch
    // PluginNotFoundExceptions.
    try {
      return $this->getFactory()->createInstance($plugin_id, $configuration);
    }
    catch (PluginNotFoundException $e) {
      $default = $this->handlePluginNotFound($plugin_id, $configuration);
      if ($default) {
        return $default;
      }
      // If there is no default plugin either: rethrow the exception.
      throw $e;
    }
  }

  /**
   * Allows plugin managers to specify custom behavior if a plugin is not found.
   *
   * @param string $plugin_id
   *   The ID of the missing requested plugin.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return object|null
   *   A fallback plugin instance if this manager is capable, NULL otherwise.
   */
  protected function handlePluginNotFound($plugin_id, array $configuration) {
    if ($this instanceof FallbackPluginManagerInterface) {
      $fallback_id = $this->getFallbackPluginId($plugin_id, $configuration);
      return $this->getFactory()->createInstance($fallback_id, $configuration);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    if (!$this->mapper) {
      throw new \BadMethodCallException(sprintf('%s does not support this method unless %s::$mapper is set.', static::class, static::class));
    }
    return $this->mapper->getInstance($options);
  }

}
