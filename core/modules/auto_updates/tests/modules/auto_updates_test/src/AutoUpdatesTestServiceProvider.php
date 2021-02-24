<?php

namespace Drupal\auto_updates_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Service provider for the auto_updates_test module.
 */
class AutoUpdatesTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    // Check if the specified constant is defined. We cannot use the state
    // service here because the container is still being built.
    if (defined('AUTO_UPDATES_TEST_DUPLICATE_SERVICE')) {
      $definition = $container->getDefinition('auto_updates_test.checker');
      $container->setDefinition('auto_updates_test.checker_duplicate', $definition);
    }
    if (defined('AUTO_UPDATES_TEST_SET_PRIORITY')) {
      $definition = $container->getDefinition('auto_updates_test.checker');
      $tags = $definition->getTags();
      $tags['auto_updates.readiness_checker'] = [['priority' => AUTO_UPDATES_TEST_SET_PRIORITY]];
      $definition->setTags($tags);
      $container->setDefinition('auto_updates_test.checker', $definition);
    }
  }

}
