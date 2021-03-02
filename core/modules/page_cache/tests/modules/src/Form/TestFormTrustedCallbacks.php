<?php

namespace Drupal\page_cache_form_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

class TestFormTrustedCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #process callback for:
   *
   * page_cache_form_test_form_page_cache_form_test_alter().
   */
  public static function pageCacheProcess(array &$element, FormStateInterface $form_state, array &$form) {
    if (isset($form_state->getBuildInfo()['immutable']) && $form_state->getBuildInfo()['immutable']) {
      $element['#suffix'] = 'Immutable: TRUE';
    }
    else {
      $element['#suffix'] = 'Immutable: FALSE';
    }
    return $element;
  }

  /**
   * @inheritDoc
   */
  public static function trustedCallbacks() {
    return ['pageCacheProcess'];
  }

}
