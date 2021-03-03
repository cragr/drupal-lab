<?php

namespace Drupal\media_library\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements TrustedCallbacks for media_library.
 *
 * @package Drupal\media_library\Form
 */
class TrustedFormCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #after_build callback for media_library_form_alter().
   */
  public static function afterBuildViewsExposedForm(array &$element, FormStateInterface $form_state) {
    // Remove .form-actions from the view's exposed filter actions. This
    // prevents the "Apply filters" submit button from being moved into the
    // dialog's button area.
    // @see \Drupal\Core\Render\Element\Actions::processActions
    // @see Drupal.behaviors.dialog.prepareDialogButtons
    // @todo Remove this after
    //   https://www.drupal.org/project/drupal/issues/3089751 is fixed.
    if (($key = array_search('form-actions', $form['actions']['#attributes']['class'])) !== FALSE) {
      unset($form['actions']['#attributes']['class'][$key]);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['afterBuildViewsExposedForm'];
  }

}
