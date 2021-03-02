<?php

namespace Drupal\content_translation;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

class ContentTranslationTrustedCallbacks implements TrustedCallbackInterface {

  /**
   * Implements #process callback for content_translation_element_info_alter()
   *
   * Expands the language_configuration form element.
   */
  public static function LanguageConfigurationElementProcess(array &$element, FormStateInterface $form_state, array &$form) {
    if (empty($element['#content_translation_skip_alter']) && \Drupal::currentUser()->hasPermission('administer content translation')) {
      $key = $element['#name'];
      $form_state->set(['content_translation', 'key'], $key);
      $context = $form_state->get(['language', $key]);

      $element['content_translation'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable translation'),
        // For new bundle, we don't know the bundle name yet,
        // default to no translatability.
        '#default_value' => $context['bundle'] ? \Drupal::service('content_translation.manager')->isEnabled($context['entity_type'], $context['bundle']) : FALSE,
        '#element_validate' => ['content_translation_language_configuration_element_validate'],
      ];

      $submit_name = isset($form['actions']['save_continue']) ? 'save_continue' : 'submit';
      // Only add the submit handler on the submit button if the #submit property
      // is already available, otherwise this breaks the form submit function.
      if (isset($form['actions'][$submit_name]['#submit'])) {
        $form['actions'][$submit_name]['#submit'][] = 'content_translation_language_configuration_element_submit';
      }
      else {
        $form['#submit'][] = 'content_translation_language_configuration_element_submit';
      }
    }
    return $element;
  }

  /**
   * @inheritDoc
   */
  public static function trustedCallbacks() {
    return ['LanguageConfigurationElementProcess'];
  }

}
