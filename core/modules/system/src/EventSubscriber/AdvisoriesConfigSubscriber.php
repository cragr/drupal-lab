<?php

namespace Drupal\update\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\update\SecurityAdvisories\SecurityAdvisoriesFetcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the stored response from the advisories feed, if needed.
 */
class AdvisoriesConfigSubscriber implements EventSubscriberInterface {

  /**
   * The update expirable key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * Constructs a new ConfigSubscriber object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_factory
   *   The expirable key/value factory.
   */
  public function __construct(KeyValueExpirableFactoryInterface $key_value_factory) {
    $this->keyValueExpirable = $key_value_factory->get('update');
  }

  /**
   * Deletes the stored response from the security advisories feed, if needed.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() === 'update.settings' && $event->isChanged('advisories.interval_hours')) {
      $original_interval = $saved_config->getOriginal('advisories.interval_hours');
      if ($original_interval && $saved_config->get('advisories.interval_hours') < $original_interval) {
        // If the new interval is less than the original interval, delete the
        // stored results.
        $this->keyValueExpirable->delete(SecurityAdvisoriesFetcher::ADVISORIES_RESPONSE_EXPIRABLE_KEY);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave'];
    return $events;
  }

}
