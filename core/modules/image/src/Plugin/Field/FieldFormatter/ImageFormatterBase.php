<?php

namespace Drupal\image\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Base class for image file formatters.
 */
abstract class ImageFormatterBase extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_loading' => [
        'priority' => 'lazy',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $description_link = $this->t('Select the lazy-load priority for load images. <a href=":link">Learn more.</a>', [
      ':link' => 'https://html.spec.whatwg.org/multipage/urls-and-fetching.html#lazy-loading-attributes',
    ]);
    $lazy_load_options = [
      'lazy' => $this->t('Lazy'),
      'eager' => $this->t('Eager'),
    ];
    $performance_description = $this->t('By default, all image assets are rendered with native browser lazy loading attributes included (<em>loading="lazy"</em>). This improves performance by allowing <a href=":link">modern browsers</a> to lazily load images without JavaScript. It is sometimes desirable to override this default to force browsers to download an image as soon as possible using the "<em>eager</em>" value instead.', [':link' => 'https://caniuse.com/loading-lazy-attr']);

    $image_loading_settings = $this->getSetting('image_loading');
    $element['image_loading'] = [
      '#type' => 'details',
      '#title' => $this->t('Performance'),
      '#weight' => 10,
      '#description' => $performance_description,
    ];
    $element['image_loading']['priority'] = [
      '#title' => $this->t('Lazy loading priority'),
      '#type' => 'select',
      '#default_value' => $image_loading_settings['priority'],
      '#options' => $lazy_load_options,
      '#description' => $description_link,
      '#empty_value' => 'auto',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $image_loading_settings = $this->getSetting('image_loading');
    $summary[] = $this->t('Lazy loading priority: @priority', [
      '@priority' => $image_loading_settings['priority'],
    ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    // Add the default image if needed.
    if ($items->isEmpty()) {
      $default_image = $this->getFieldSetting('default_image');
      // If we are dealing with a configurable field, look in both
      // instance-level and field-level settings.
      if (empty($default_image['uuid']) && $this->fieldDefinition instanceof FieldConfigInterface) {
        $default_image = $this->fieldDefinition->getFieldStorageDefinition()->getSetting('default_image');
      }
      if (!empty($default_image['uuid']) && $file = \Drupal::service('entity.repository')->loadEntityByUuid('file', $default_image['uuid'])) {
        // Clone the FieldItemList into a runtime-only object for the formatter,
        // so that the fallback image can be rendered without affecting the
        // field values in the entity being rendered.
        $items = clone $items;
        $items->setValue([
          'target_id' => $file->id(),
          'alt' => $default_image['alt'],
          'title' => $default_image['title'],
          'width' => $default_image['width'],
          'height' => $default_image['height'],
          'entity' => $file,
          '_loaded' => TRUE,
          '_is_default' => TRUE,
        ]);
        $file->_referringItem = $items[0];
      }
    }

    return parent::getEntitiesToView($items, $langcode);
  }

}
