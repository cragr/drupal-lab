<?php

/**
 * @file
 * Post-update functions for Image.
 */

/**
 * Implements hook_removed_post_updates().
 */
function image_removed_post_updates() {
  return [
    'image_post_update_image_style_dependencies' => '9.0.0',
    'image_post_update_scale_and_crop_effect_add_anchor' => '9.0.0',
  ];
}

/**
 * Implements hook_post_update_NAME().
 *
 * Add the image loading 'priority' setting to all
 * image field formatter instances.
 */
function image_post_update_image_loading_priority() {
  $storage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
  foreach ($storage->loadMultiple() as $id => $view_display) {
    $changed = FALSE;
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    foreach ($view_display->getComponents() as $field => $component) {
      if (isset($component['type']) && ($component['type'] === 'image')) {
        $component['settings']['image_loading']['priority'] = 'lazy';
        $view_display->setComponent($field, $component);
        $changed = TRUE;
      }
    }
    if ($changed) {
      $view_display->save();
    }
  }
}
