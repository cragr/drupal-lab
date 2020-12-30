<?php

namespace Drupal\auto_updates\ReadinessChecker;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;

/**
 * Defines a manager to run readiness checkers.
 */
class ReadinessCheckerManager {

  /**
   * Time (in seconds) since the last check after which we generate a warning.
   *
   * The value is equal to 1 day.
   */
  private const LAST_CHECKED_WARNING = 60 * 60 * 24;

  /**
   * The key/value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueExpirable;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * An array of active checkers.
   *
   * The keys are integers that indicate priority. Values are arrays of
   * ReadinessCheckerInterface objects.
   *
   * @var \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface[][]
   */
  protected $checkersByPriority = [];

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * ReadinessCheckerManager constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable
   *   The config factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(KeyValueExpirableFactoryInterface $key_value_expirable, ConfigFactoryInterface $config_factory, TimeInterface $time) {
    $this->keyValueExpirable = $key_value_expirable->get('auto_updates');
    $this->configFactory = $config_factory;
    $this->time = $time;
  }

  /**
   * Appends a checker to the checker chain.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface $checker
   *   The checker interface to be appended to the checker chain.
   * @param int $priority
   *   (optional) The priority of the checker being added. Defaults to 0.
   *   Readiness checkers with larger priorities will run first within a
   *   category.
   *
   * @return $this
   */
  public function addChecker(ReadinessCheckerInterface $checker, int $priority = 0): ReadinessCheckerManager {
    $this->checkersByPriority[$priority][] = $checker;
    ksort($this->checkersByPriority);
    return $this;
  }

  /**
   * Runs readiness checks.
   *
   * @param bool $refresh
   *   (optional) Whether to refresh the results, defaults FALSE. If FALSE then
   *   cached results will be returned if available.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]
   *   A nested array of readiness check messages. The top level array is keyed
   *   by category and the next level array is an array of translatable strings
   *   for the category.
   */
  protected function run(bool $refresh = FALSE): array {
    if ($refresh) {
      $this->keyValueExpirable->delete('readiness_check_results');
    }
    else {
      $results = $this->keyValueExpirable->get('readiness_check_results');

      // If the checkers have not changed return the results.
      if ($results && $results['checkers'] === $this->getCurrentCheckerIds()) {
        return $results['results'];
      }
      $this->keyValueExpirable->delete('readiness_check_results');
    }

    $sorted_checkers = $this->getSortedCheckers();
    $results = [];
    foreach ($sorted_checkers as $checker) {
      $result = ReadinessCheckerResult::createFromReadinessChecker($checker);
      if (!$result->isEmpty()) {
        $results[] = $result;
      }
    }

    $this->keyValueExpirable->setWithExpire(
      'readiness_check_results',
      [
        'results' => $results,
        'checkers' => $this->getCurrentCheckerIds(),
      ],
      3600
    );
    $this->keyValueExpirable->set('readiness_check_timestamp', $this->time->getRequestTime());
    return $results;
  }

  /**
   * Gets the timestamp of the most recent run.
   *
   * @return int|null
   *   The timestamp of the most recently completed run, or NULL if no run has
   *   been completed.
   */
  public function getMostRecentRunTime():?int {
    return $this->keyValueExpirable->get('readiness_check_timestamp');
  }

  /**
   * Sorts checkers according to priority.
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface[]
   *   A sorted array of checker objects ordered by priority.
   */
  protected function getSortedCheckers(): array {
    $sorted = [];
    foreach ($this->checkersByPriority as $checkers) {
      $sorted = array_merge($sorted, $checkers);
    }
    return $sorted;
  }

  /**
   * Gets the current checker service Ids.
   *
   * @return string
   *   A concatenated list of checker service IDs delimited by '::'.
   */
  protected function getCurrentCheckerIds(): string {
    $service_ids = [];
    foreach ($this->getSortedCheckers() as $checker) {
      $service_ids[] = $checker->_serviceId;
    }
    return implode('::', $service_ids);
  }

  /**
   * Determines whether the readiness checkers have been run recently.
   *
   * @return bool
   *   TRUE if the checkers have been run recently, otherwise FALSE.
   */
  public function hasRunRecently(): bool {
    return $this->time->getRequestTime() <= $this->getMostRecentRunTime() + self::LAST_CHECKED_WARNING;
  }

  /**
   * @param bool $refresh
   *
   * @return \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult[]
   */
  public function getResults(bool $refresh = FALSE) {
    return $this->run($refresh);
  }

}
