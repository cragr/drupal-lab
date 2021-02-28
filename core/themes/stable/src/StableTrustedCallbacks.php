<?php

namespace Drupal\stable;

use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Security\TrustedCallbackInterface;

class StableTrustedCallbacks implements TrustedCallbackInterface {

  /**
   * #process callback, for adding classes to filter components.
   *
   * @param array $element
   *   Render array for the text_format element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current form state.
   * @param array $form
   *   The complete form.
   *
   * @return array
   *   Text_format element with the filter classes added.
   */
  public static function processTextFormat(array &$element, FormStateInterface $formState, array &$form) {
    $element['format']['#attributes']['class'][] = 'filter-wrapper';
    $element['format']['guidelines']['#attributes']['class'][] = 'filter-guidelines';
    $element['format']['format']['#attributes']['class'][] = 'filter-list';
    $element['format']['help']['#attributes']['class'][] = 'filter-help';

    return $element;
  }

  /**
   * @inheritDoc
   */
  public static function trustedCallbacks() {
    return ['processTextFormat'];
  }

}
