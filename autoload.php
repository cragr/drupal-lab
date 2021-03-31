<?php

/**
 * @file
 * Includes the autoloader created by Composer and defines DRUPAL_ROOT.
 *
 * @see composer.json
 * @see index.php
 * @see core/install.php
 * @see core/rebuild.php
 * @see core/modules/statistics/statistics.php
 */

/**
 * Defines the root directory of the Drupal installation.
 *
 * Defining the DRUPAL_ROOT constant here ensures that it holds the actual
 * location of the webroot (where this file is placed scaffolding), even if the
 * rest of Drupal is symlinked into the Composer project.
 */
define('DRUPAL_ROOT', __DIR__);

return require __DIR__ . '/vendor/autoload.php';
