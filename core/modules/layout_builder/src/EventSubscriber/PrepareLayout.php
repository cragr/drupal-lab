<?php

namespace Drupal\layout_builder\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\Event\PrepareLayoutEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber to prepare section storage via the
 * \Drupal\layout_builder\Event\PrepareLayoutEvent.
 *
 * @internal
 *   Tagged services are internal.
 *
 * @see \Drupal\layout_builder\Event\PrepareLayoutEvent
 * @see \Drupal\layout_builder\Element\LayoutBuilder::prepareLayout()
 */
class PrepareLayout implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * Constructs a new PrepareLayout.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The tempstore repository.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[LayoutBuilderEvents::PREPARE_LAYOUT][] = ['onPrepareLayout', 10];
    return $events;
  }

  /**
   * Prepares a layout for use in the UI.
   *
   * @param \Drupal\layout_builder\Event\PrepareLayoutEvent $event
   *   The prepare layout event.
   */
  public function onPrepareLayout(PrepareLayoutEvent $event) {
    $section_storage = $event->getSectionStorage();

    // If the layout has pending changes, add a warning.
    if (!$this->layoutTempstoreRepository->has($section_storage)) {
      // If the layout is an override that has not yet been overridden, copy the
      // sections from the corresponding default.
      if ($section_storage instanceof OverridesSectionStorageInterface && !$section_storage->isOverridden()) {
        $sections = $section_storage->getDefaultSectionStorage()->getSections();
        foreach ($sections as $section) {
          $section_storage->appendSection($section);
        }
      }
      // Add storage to tempstore regardless of what the storage is.
      $this->layoutTempstoreRepository->set($section_storage);
    }
  }

}
