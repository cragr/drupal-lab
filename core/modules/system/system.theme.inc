<?php

use Drupal\Core\Url;

/**
 * @file
 * Preprocessors functions for the system module.
 */

/**
 * Prepares variables for system fetch error message templates.
 *
 * Default template: system-fetch-error-message.html.twig.
 *
 * @param array $variables
  *   An associative array of template variables.
 */
function template_preprocess_system_fetch_error_message(array &$variables): void {
  $variables['error_message'] = [
    'message' => [
      '#markup' => t('Failed to fetch security advisory data:'),
    ],
    'items' => [
      '#theme' => 'item_list',
      '#items' => [
        'documentation_link' => t('See <a href="@url">PHP OpenSSL requirements</a> in the Drupal.org handbook for possible reasons this could happen and what you can do to resolve them.', ['@url' => 'https://www.drupal.org/node/3170647']),
      ],
    ],
  ];
  if (\Drupal::moduleHandler()->moduleExists('dblog') && \Drupal::currentUser()->hasPermission('access site reports')) {
    $options = ['query' => ['type' => ['update']]];
    $dblog_url = Url::fromRoute('dblog.overview', [], $options);
    $variables['error_message']['items']['#items']['dblog'] = t('Check <a href="@url">your local system logs</a> for additional error messages.', ['@url' => $dblog_url->toString()]);
  }
  else {
    $variables['error_message']['items']['#items']['logs'] = t('Check your local system logs for additional error messages.');
  }

}
