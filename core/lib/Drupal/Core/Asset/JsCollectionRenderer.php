<?php

namespace Drupal\Core\Asset;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\QueryStringInterface;
use Drupal\Core\State\StateInterface;

/**
 * Renders JavaScript assets.
 */
class JsCollectionRenderer implements AssetCollectionRendererInterface {

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0.
   */
  protected $state;

  /**
   * The cache query string service.
   *
   * @var \Drupal\Core\Cache\QueryStringInterface
   */
  protected $queryString;

  /**
   * Constructs a JsCollectionRenderer.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key/value store.
   * @param \Drupal\Core\Cache\QueryStringInterface $query_string
   *   The cache query string service.
   */
  public function __construct(StateInterface $state, QueryStringInterface $query_string = NULL) {
    @trigger_error('$state parameter is deprecated since drupal:9.2.0 and removed from drupal:10.0.0.', \E_USER_DEPRECATED);
    $this->state = $state;
    if ($query_string === NULL) {
      @trigger_error('$query_string parameter is added since drupal:9.2.0 and is required from drupal:10.0.0.', \E_USER_DEPRECATED);
      $query_string = \Drupal::service('cache.query_string');
    }
    $this->queryString = $query_string;
  }

  /**
   * {@inheritdoc}
   *
   * This class evaluates the aggregation enabled/disabled condition on a group
   * by group basis by testing whether an aggregate file has been made for the
   * group rather than by testing the site-wide aggregation setting. This allows
   * this class to work correctly even if modules have implemented custom
   * logic for grouping and aggregating files.
   */
  public function render(array $js_assets) {
    $elements = [];

    // A dummy query-string is added to filenames, to gain control over
    // browser-caching. The string changes on every update or full cache
    // flush, forcing browsers to load a new copy of the files, as the
    // URL changed. Files that should not be cached get REQUEST_TIME as
    // query-string instead, to enforce reload on every page request.
    $default_query_string = $this->queryString->get();

    // Defaults for each SCRIPT element.
    $element_defaults = [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => '',
    ];

    // Loop through all JS assets.
    foreach ($js_assets as $js_asset) {
      // Element properties that do not depend on JS asset type.
      $element = $element_defaults;
      $element['#browsers'] = $js_asset['browsers'];

      // Element properties that depend on item type.
      switch ($js_asset['type']) {
        case 'setting':
          $element['#attributes'] = [
            // This type attribute prevents this from being parsed as an
            // inline script.
            'type' => 'application/json',
            'data-drupal-selector' => 'drupal-settings-json',
          ];
          $element['#value'] = Json::encode($js_asset['data']);
          break;

        case 'file':
          $query_string = $js_asset['version'] == -1 ? $default_query_string : 'v=' . $js_asset['version'];
          $query_string_separator = (strpos($js_asset['data'], '?') !== FALSE) ? '&' : '?';
          $element['#attributes']['src'] = file_url_transform_relative(file_create_url($js_asset['data']));
          // Only add the cache-busting query string if this isn't an aggregate
          // file.
          if (!isset($js_asset['preprocessed'])) {
            $element['#attributes']['src'] .= $query_string_separator . ($js_asset['cache'] ? $query_string : REQUEST_TIME);
          }
          break;

        case 'external':
          $element['#attributes']['src'] = $js_asset['data'];
          break;

        default:
          throw new \Exception('Invalid JS asset type.');
      }

      // Attributes may only be set if this script is output independently.
      if (!empty($element['#attributes']['src']) && !empty($js_asset['attributes'])) {
        $element['#attributes'] += $js_asset['attributes'];
      }

      $elements[] = $element;
    }

    return $elements;
  }

}
