<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->merge('key_value')
  ->fields([
    'value' => 'i:8000;',
    'name' => 'tour',
    'collection' => 'system.schema',
  ])
  ->condition('collection', 'system.schema')
  ->condition('name', 'tour')
  ->execute();

// Enable tour and tour_test modules.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['tour'] = 0;
$extensions['module']['tour_test'] = 0;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
    'collection' => '',
    'name' => 'core.extension',
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

$config_path = drupal_get_path('module', 'tour_test') . '/config/install/';
$source = new FileStorage($config_path);
$config_storage = \Drupal::service('config.storage');
$config_storage->write('tour.tour.tour-test-legacy', $source->read('tour.tour.tour-test-legacy'));
