<?php

namespace Drupal\system\Form;

/**
 * Builds a confirmation form for enabling experimental modules.
 *
 * @internal
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0.
 *   Use \Drupal\system\Form\ExtensionConfirmForm instead. As internal API,
 *   ModulesListConfirmForm may also be removed in a minor release.
 *
 * @see https://www.drupal.org/node/3188194
 */
class ModulesListExperimentalConfirmForm extends ModulesListConfirmForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you wish to enable experimental modules?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_modules_experimental_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildMessageList() {
    $this->messenger()->addWarning($this->t('<a href=":url">Experimental modules</a> are provided for testing purposes only. Use at your own risk.', [':url' => 'https://www.drupal.org/core/experimental']));

    $items = parent::buildMessageList();
    // Add the list of experimental modules after any other messages.
    $items[] = $this->t('The following modules are experimental: @modules', ['@modules' => implode(', ', array_values($this->modules['experimental']))]);

    return $items;
  }

}
