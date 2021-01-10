<?php

namespace Drupal\taxonomy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if ($route = $collection->get('entity.taxonomy_vocabulary.collection')) {
      $route->setOption('bundle_of', 'taxonomy_term');
    }
  }

}
