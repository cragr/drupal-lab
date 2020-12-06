<?php

/**
 * @file
 * Post update functions for Node.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function node_removed_post_updates() {
  return [
    'node_post_update_configure_status_field_widget' => '9.0.0',
    'node_post_update_node_revision_views_data' => '9.0.0',
  ];
}

/**
 * Add plural label variants to node-type entities.
 */
function node_post_update_plural_variants(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'node_type', function (): bool {
    return TRUE;
  });
}
