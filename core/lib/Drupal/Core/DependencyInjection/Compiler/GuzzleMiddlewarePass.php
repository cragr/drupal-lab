<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds middleware IDs to the http_handler_stack_configurator service.
 */
class GuzzleMiddlewarePass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $middleware_ids = array_keys($container->findTaggedServiceIds('http_client_middleware'));
    $container->getDefinition('http_handler_stack_configurator')
      ->addArgument($middleware_ids);
  }

}
