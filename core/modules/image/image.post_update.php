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
 * Fix problem with image dimensions when using multiple upload.
 */
function image_post_update_multiple_upload_fix_with_dimensions() {
  \Drupal::messenger()->addMessage(t('Fixed problem with incorrect processing of image dimensions when using multiple upload. To eliminate this problem for already existing records see <a href="https://www.drupal.org/project/drupal/issues/2967586">https://www.drupal.org/project/drupal/issues/2967586</a>'), 'status');
}
