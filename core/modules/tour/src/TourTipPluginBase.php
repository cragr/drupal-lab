<?php

namespace Drupal\tour;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base tour item implementation.
 *
 * @see \Drupal\tour\Annotation\Tip
 * @see \Drupal\tour\TipPluginInterface
 * @see \Drupal\tour\TourTipPluginInterface
 * @see \Drupal\tour\TipPluginManager
 * @see plugin_api
 *
 * @todo remove TourTipInterface implementation in
 *    https://drupal.org/node/3195193
 */
abstract class TourTipPluginBase extends PluginBase implements TipPluginInterface, TourTipPluginInterface {

  /**
   * The label which is used for render of this tip.
   *
   * @var string
   */
  protected $label;

  /**
   * Allows tips to take more priority that others.
   *
   * @var string
   */
  protected $weight;

  /**
   * The attributes that will be applied to the markup of this tip.
   *
   * @var array
   */
  protected $attributes;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    if (!empty($this->configuration[$key])) {
      return $this->configuration[$key];
    }
  }

  /**
   * The title of the tour tip.
   *
   * This is what is displayed in the tip's header. It may differ from the tip
   * label, which is defined in the tip's configuration.
   * This is mapped to the `title` property of the Shepherd tooltip options.
   *
   * @return string
   *   The title.
   */
  public function getTitle() {
    return Html::escape($this->getLabel());
  }

  /**
   * Determines the placement of the tip relative to the element.
   *
   * If null, the tip will automatically determine the best position based on
   * the element's position in the viewport.
   *
   * @return string|null
   *   The tip placement relative to the element.
   *
   * @see https://shepherdjs.dev/docs/Step.html
   */
  public function getLocation() {
    $location = $this->get('position');

    // The location values accepted by PopperJS, the library used for
    // positioning the tip.
    $valid_values = [
      'auto',
      'auto-start',
      'auto-end',
      'top',
      'top-start',
      'top-end',
      'bottom',
      'bottom-start',
      'bottom-end',
      'right',
      'right-start',
      'right-end',
      'left',
      'left-start',
      'left-end',
    ];

    if (!is_null($location)) {
      // This assertion is skipped if `$location` is null, as that instructs
      // Shepherd to use automatic positioning.
      assert(in_array(trim($location), $valid_values), "$location is not a valid Tour Tip position value.");
    }

    return in_array(trim($location), $valid_values) ? $location : NULL;
  }

  /**
   * The selector the tour tip will attach to.
   *
   * This is mapped to the `attachTo.element` property of the Shepherd tooltip
   * options.
   *
   * @return null|string
   *   A selector string, or null for an unattached tip.
   *
   * @see https://shepherdjs.dev/docs/Step.html
   *
   * @todo this can probably be simplified in https://drupal.org/node/3195193
   *    to `$this->get('selector')`.
   */
  public function getSelector() {
    // If selector isn't null, return immediately. If it is null, it may be
    // intentional, but it may also be due to the selector value being provided
    // in deprecated Joyride config. Check for that before returning a value.
    if ($selector = $this->get('selector')) {
      return $selector;
    }

    $attributes = $this->get('attributes');
    if (isset($attributes['data-id'])) {
      $selector = "#{$attributes['data-id']}";
    }
    elseif (isset($attributes['data-class'])) {
      $selector = ".{$attributes['data-class']}";
    }
    return $selector;
  }

  /**
   * Provides an identifier used by Joyride backwards compatible markup.
   *
   * Joyride wraps its below-title content in a `<p>`. Core tourTip
   * implementations include a `.tour-tip-(foo)` class. This method returns the
   * `Foo`. It is typically the plugin type, but not always.
   *
   * @return string
   *   The identifier used in creating a BC Joyride class.
   *
   * @todo deprecate in https://drupal.org/node/3195193
   * @todo this property can be removed when the Stable 9 theme is removed from
   *   core. It only exists to provide Joyride backwards compatibility.
   */
  public function getJoyrideContentContainerName() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * This method should not actually be used and throws an exception.
   *
   * This method exists so the class can implement TipPluginInterface, which is
   * needed for plugin discovery in Drupal 9. TipPluginInterface is deprecated
   * and will be replaced with TourTipPluginInterface in Drupal 10.
   *
   * @return array
   *   An empty array.
   *
   * @todo remove in https://drupal.org/node/3195193
   */
  final public function getAttributes() {
    throw new \Exception('\Drupal\tour\TourTipPluginBase::getAttributes is not supported. Use getSelector() for the selector of the element the tip is associated with, and get() for other tip config properties.');

    // This is never reached due to the exception above, but is here to meet the
    // requirements of implementing TipPluginInterface.
    // phpcs:ignore
    return [];
  }

  /**
   * This method should not actually be used and throws an exception.
   *
   * This method exists so the class can implement TipPluginInterface, which is
   * needed for plugin discovery in Drupal 9. TipPluginInterface is deprecated
   * and will be replaced with TourTipPluginInterface in Drupal 10.
   *
   * @return array
   *   An empty array, were an exception not intentionally thrown.
   *
   * @todo remove in https://drupal.org/node/3195193
   */
  final public function getOutput() {
    // Intentionally return an empty array. This method exists so the class
    // implements TipPluginInterface, which is needed for plugin discovery in
    // Drupal 9, but is a deprecated interface.
    throw new \Exception('\Drupal\tour\TourTipPluginBase::getOutput is not supported. Use getBody() and getTitle() instead. ');

    // This is never reached due to the exception above, but is here to meet the
    // requirements of implementing TipPluginInterface.
    // phpcs:ignore
    return [];
  }

}
