<?php

namespace Drupal\tour;

/**
 * Defines an interface for tour items.
 *
 * @see \Drupal\tour\Annotation\Tip
 * @see \Drupal\tour\TipPluginBase
 * @see \Drupal\tour\TipPluginManager
 * @see plugin_api
 */
interface TourTipPluginInterface {

  /**
   * Returns id of the tip.
   *
   * @return string
   *   The id of the tip.
   */
  public function id();

  /**
   * Returns label of the tip.
   *
   * @return string
   *   The label of the tip.
   */
  public function getLabel();

  /**
   * Returns weight of the tip.
   *
   * @return string
   *   The weight of the tip.
   */
  public function getWeight();

  /**
   * Used for returning values by key.
   *
   * @var string
   *   Key of the value.
   *
   * @return string
   *   Value of the key.
   */
  public function get($key);

  /**
   * Used for returning values by key.
   *
   * @var string
   *   Key of the value.
   *
   * @var string
   *   Value of the key.
   */
  public function set($key, $value);

  /**
   * The selector the tour tip will attach to.
   *
   * @return null|string
   *   A selector string, or null for an unattached tip.
   */
  public function getSelector();

  /**
   * Provides the body content of the tooltip.
   *
   * This is mapped to the `text` property of the Shepherd tooltip options.
   *
   * @return array
   *   A render array.
   */
  public function getBody();

  /**
   * The title of the tour tip.
   *
   * This is what is displayed in the tip's header. It may differ from the tip
   * label, which is defined in the tip's configuration.
   *
   * @return string
   *   The title.
   */
  public function getTitle();

}
