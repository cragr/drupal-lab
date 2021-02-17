<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for generating the readiness checkers' output for hook_requirements.
 *
 * @see update_requirements()
 *
 * @internal
 *   This class implements logic output the messages from readiness checkers. It
 *   should not be called directly.
 */
final class ReadinessRequirement implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use ReadinessCheckerTrait;
  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * ReadinessRequirement constructor.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerManager $readinessCheckerManager
   *   The readiness checker manager service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(ReadinessCheckerManager $readinessCheckerManager, TranslationInterface $translation, DateFormatterInterface $date_formatter) {
    $this->readinessCheckerManager = $readinessCheckerManager;
    $this->setStringTranslation($translation);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container):self {
    return new static(
      $container->get('auto_updates.readiness_checker_manager'),
      $container->get('string_translation'),
      $container->get('date.formatter')
    );
  }

  /**
   * Gets requirements arrays to as specified in hook_requirements.
   *
   * @return array
   *   Requirements arrays as specified by hook_requirements().
   */
  public function getRequirements(): array {
    $run_link = $this->createRunLink();

    $last_check_timestamp = $this->readinessCheckerManager->getMostRecentRunTime();
    if ($last_check_timestamp === NULL) {
      $requirement['title'] = $this->t('Update readiness checks');
      $requirement['severity'] = SystemManager::REQUIREMENT_WARNING;
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      $requirement['value'] = $this->t('Your site has never checked if it is ready to apply automatic updates.');
      if ($run_link) {
        $requirement['description'] = $run_link;
      }
      return ['auto_updates_readiness' => $requirement];
    }
    elseif (!$this->readinessCheckerManager->hasRunRecently()) {
      $requirement['title'] = $this->t('Update readiness checks');
      $requirement['severity'] = SystemManager::REQUIREMENT_WARNING;
      $time_ago = $this->dateFormatter->formatTimeDiffSince($last_check_timestamp);
      // @todo Link "automatic updates" to documentation in
      //   https://www.drupal.org/node/3168405.
      $requirement['value'] = $this->t('Your site has not recently checked if it is ready to apply automatic updates.');
      $requirement['description']['message']['#markup'] = $this->t('Readiness checks were last run @time ago.', ['@time' => $time_ago]);
      if ($run_link) {
        $requirement['description']['run_link'] = [
          '#type' => 'container',
          '#markup' => $run_link,
        ];
      }
      return ['auto_updates_readiness' => $requirement];
    }
    else {
      $requirements = [];
      foreach ([SystemManager::REQUIREMENT_WARNING => 'warnings', SystemManager::REQUIREMENT_ERROR => 'errors'] as $severity => $severity_type) {
        if ($requirement = $this->createRequirementForSeverity($severity)) {
          $requirements["auto_updates_readiness_$severity_type"] = $requirement;
        }
      }
      if (empty($requirements)) {
        $requirements['auto_updates_readiness'] = [
          'title' => $this->t('Update readiness checks'),
          'severity' => SystemManager::REQUIREMENT_OK,
          // @todo Link "automatic updates" to documentation in
          //   https://www.drupal.org/node/3168405.
          'value' => $this->t('Your site is ready for automatic updates.'),
        ];
        if ($run_link) {
          $requirements['auto_updates_readiness']['description'] = $run_link;
        }
      }
      return $requirements;
    }
  }

  /**
   * Creates a requirements section for readiness checker results.
   *
   * @param int $severity
   *   The severity for requirement section.
   *
   * @return array|null
   *   Requirements array as specified by hook_requirements(), or NULL
   *   if no requirements can be determined.
   */
  protected function createRequirementForSeverity(int $severity): ?array {
    $severity_messages = [];
    foreach ($this->getResultsWithMessagesForSeverity($severity) as $result) {
      if ($severity === SystemManager::REQUIREMENT_ERROR) {
        $summary = $result->getErrorsSummary();
        $checker_messages = $result->getErrorMessages();
      }
      elseif ($severity === SystemManager::REQUIREMENT_WARNING) {
        $summary = $result->getWarningsSummary();
        $checker_messages = $result->getWarningMessages();
      }
      else {
        throw new \InvalidArgumentException('Unknown severity type: ' . $severity);
      }
      if (count($checker_messages) === 1) {
        $severity_messages[] = ['#markup' => array_pop($checker_messages)];
      }
      else {
        $severity_messages[] = [
          '#type' => 'details',
          '#title' => $summary,
          '#open' => FALSE,
          'messages' => [
            '#theme' => 'item_list',
            '#items' => $checker_messages,
          ],
        ];
      }
    }
    if ($severity_messages) {
      $requirement = [
        'title' => $this->t('Update readiness checks'),
        'severity' => $severity,
        'description' => [
          'messages' => $severity_messages,
          'run_link' => [
            '#type' => 'container',
            '#markup' => $this->createRunLink(),
          ],
        ],
      ];
      $requirement['value'] = $this->getFailureMessageForSeverity($severity);
      return $requirement;
    }
    return NULL;
  }

  /**
   * Creates a link to run the readiness checkers.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   If the user has access to run the readiness checker then a link to run
   *   the checkers, otherwise NULL.
   */
  protected function createRunLink(): ?TranslatableMarkup {
    $readiness_check_url = Url::fromRoute('auto_updates.update_readiness', ['display_message_on_fails' => TRUE]);
    if ($readiness_check_url->access()) {
      return $this->t(
        '<a href=":link">Run readiness checks</a> now.',
        [':link' => $readiness_check_url->toString()]
      );
    }
    return NULL;
  }

}
