<?php

/**
 * @file
 * Miscellaneous functions.
 */

use Drupal\Core\Cache\Rebuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rebuilds all caches even when Drupal itself does not work.
 *
 * @param $class_loader
 *   The class loader. Normally Composer's ClassLoader, as included by the
 *   front controller, but may also be decorated.
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The current request.
 *
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\Core\Cache\Rebuilder::safeBootstrap() instead.
 *
 * @see https://www.drupal.org/node/3014783
 * @see rebuild.php
 */
function drupal_rebuild($class_loader, Request $request) {
  @trigger_error('drupal_rebuild() is deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Cache\Rebuilder::safeBootstrap() instead. See https://www.drupal.org/node/3014783', E_USER_DEPRECATED);
  Rebuilder::safeBootstrap($class_loader, $request);
}
