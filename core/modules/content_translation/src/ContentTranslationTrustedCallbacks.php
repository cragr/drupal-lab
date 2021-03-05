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
        '#element_validate' => [
          [static::class, 'languageConfigurationElementValidate'],
        ],
      ];

      $submit_name = isset($form['actions']['save_continue']) ? 'save_continue' : 'submit';
      // Only add the submit handler on the submit button if the #submit
      // property is already available, otherwise this breaks the form submit
      // function.
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
   * Implements #element_validate callback for the method shown below.
   *
   * - ::LanguageConfigurationElementProcess()
   *
   * Checks whether translation can be enabled: if language is set to one of the
   * special languages and language selector is not hidden, translation cannot
   * be enabled.
   */
  public static function languageConfigurationElementValidate(array &$element, FormStateInterface $form_state, array &$form) {
    $key = $form_state->get(['content_translation', 'key']);
    $values = $form_state->getValue($key);
    if (!$values['language_alterable'] && $values['content_translation'] && \Drupal::languageManager()->isLanguageLocked($values['langcode'])) {
      foreach (\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_LOCKED) as $language) {
        $locked_languages[] = $language->getName();
      }
      // @todo Set the correct form element name as soon as the element parents
      //   are correctly set. We should be using NestedArray::getValue() but for
      //   now we cannot.
      $form_state->setErrorByName('', t('"Show language selector" is not compatible with translating content that has default language: %choice. Either do not hide the language selector or pick a specific language.', ['%choice' => $locked_languages[$values['langcode']]]));
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks() {
    return [
      'LanguageConfigurationElementProcess',
      'languageConfigurationElementValidate',
    ];
  }

}
