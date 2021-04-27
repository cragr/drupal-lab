<?php

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'timestamp' formatter.
 *
 * @FieldFormatter(
 *   id = "timestamp",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "timestamp",
 *     "created",
 *     "changed",
 *   }
 * )
 */
class TimestampFormatter extends FormatterBase {
  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The date format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateFormatStorage;

  /**
   * Constructs a new TimestampFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_format_storage
   *   The date format storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->dateFormatter = $date_formatter;
    $this->dateFormatStorage = $date_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('date.formatter'),
      $container->get('entity_type.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'date_format' => 'medium',
      'custom_date_format' => '',
      'timezone' => '',
      'tooltip' => [
        'date_format' => 'long',
        'custom_date_format' => '',
      ],
      'time_diff' => [
        'enabled' => FALSE,
        'future_format' => '@interval hence',
        'past_format' => '@interval ago',
        'granularity' => 2,
        'refresh' => 60,
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $date_formats = [];
    foreach ($this->dateFormatStorage->loadMultiple() as $machine_name => $value) {
      $date_formats[$machine_name] = $this->t('@name format: @date', ['@name' => $value->label(), '@date' => $this->dateFormatter->format(REQUEST_TIME, $machine_name)]);
    }
    $date_formats['custom'] = $this->t('Custom');

    $form['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Date format'),
      '#options' => $date_formats,
      '#default_value' => $this->getSetting('date_format') ?: 'medium',
    ];

    $form['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom date format'),
      '#description' => $this->t('See <a href="https://www.php.net/manual/datetime.format.php#refsect1-datetime.format-parameters" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $this->getSetting('custom_date_format') ?: '',
      '#states' => $this->buildStates(['date_format'], ['value' => 'custom']),
    ];

    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Time zone'),
      '#options' => ['' => $this->t('- Default site/user time zone -')] + system_time_zones(FALSE, TRUE),
      '#default_value' => $this->getSetting('timezone'),
    ];

    $tooltip = $this->getSetting('tooltip');
    $form['tooltip']['#tree'] = TRUE;
    $form['tooltip']['date_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Tooltip date format'),
      '#description' => $this->t('Select the date format to be used for the title and displayed on mouse hover.'),
      '#options' => $date_formats,
      '#default_value' => $tooltip['date_format'],
    ];

    $form['tooltip']['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tooltip custom date format'),
      '#description' => $this->t('See <a href="http://php.net/manual/function.date.php" target="_blank">the documentation for PHP date formats</a>.'),
      '#default_value' => $tooltip['custom_date_format'],
      '#states' => $this->buildStates(['tooltip', 'date_format'], ['value' => 'custom']),
    ];

    $time_diff = $this->getSetting('time_diff');
    $form['time_diff']['#tree'] = TRUE;
    $form['time_diff']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Display as a time difference"),
      '#description' => $this->t("Show the difference in time between the timestamp and the current time, e.g. '6 months ago'."),
      '#default_value' => $time_diff['enabled'],
    ];

    $states = $this->buildStates(['time_diff', 'enabled'], ['checked' => TRUE]);

    $form['time_diff']['future_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Future format'),
      '#description' => $this->t("Use the <code>@interval</code> placeholder to represent the formatted time difference interval. E.g. <code>@interval hence</code> will be displayed as <em>2 hours 5 minutes hence</em>."),
      '#default_value' => $time_diff['future_format'],
      '#states' => $states,
    ];

    $form['time_diff']['past_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Past format'),
      '#description' => $this->t("Use the <code>@interval</code> placeholder to represent the formatted time difference interval. E.g. <code>@interval ago</code> will be displayed as <em>2 hours 5 minutes ago</em>."),
      '#default_value' => $time_diff['past_format'],
      '#states' => $states,
    ];

    $form['time_diff']['granularity'] = [
      '#type' => 'select',
      '#title' => $this->t('Time units'),
      '#description' => $this->t("How many time units will be used in formatting the time difference. For example, if '1' is selected then the displayed time difference will only contain a single time unit such as '2 years' or '5 minutes' never '2 years 3 months' or '5 minutes 8 seconds'."),
      '#default_value' => $time_diff['granularity'],
      '#options' => array_combine(range(1, 7), range(1, 7)),
      '#states' => $states,
    ];

    $form['time_diff']['refresh'] = [
      '#type' => 'select',
      '#title' => $this->t('Refresh interval'),
      '#description' => $this->t('How often to refresh the displayed time difference. The time difference is refreshed on client-side, by JavaScript, without reloading the page.'),
      '#default_value' => $time_diff['refresh'],
      '#options' => $this->getRefreshIntervals(),
      '#states' => $states,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $date_format = $this->getSetting('date_format');
    $summary[] = $this->t('Date format: @date_format', ['@date_format' => $date_format]);
    if ($date_format === 'custom' && ($custom_date_format = $this->getSetting('custom_date_format'))) {
      $summary[] = $this->t('Custom date format: @custom_date_format', ['@custom_date_format' => $custom_date_format]);
    }
    if ($timezone = $this->getSetting('timezone')) {
      $summary[] = $this->t('Time zone: @timezone', ['@timezone' => $timezone]);
    }

    $tooltip = $this->getSetting('tooltip');
    $summary[] = $this->t('Tooltip date format: @date_format', ['@date_format' => $tooltip['date_format']]);
    if ($tooltip['date_format'] === 'custom' && $tooltip['custom_date_format']) {
      $summary[] = $this->t('Tooltip custom date format: @custom_date_format', ['@custom_date_format' => $tooltip['custom_date_format']]);
    }

    $time_diff = $this->getSetting('time_diff');
    if ($time_diff['enabled']) {
      $summary[] = $this->t('Displayed as a time difference');

      $options = ['granularity' => $time_diff['granularity']];

      $timestamp = strtotime('1 year 1 month 1 week 1 day 1 hour 1 minute');
      $interval = $this->dateFormatter->formatTimeDiffUntil($timestamp, $options);
      $display = new FormattableMarkup($time_diff['future_format'], ['@interval' => $interval]);
      $summary[] = $this->t('Future date: %display', ['%display' => $display]);

      $timestamp = strtotime('-1 year -1 month -1 week -1 day -1 hour -1 minute');
      $interval = $this->dateFormatter->formatTimeDiffSince($timestamp, $options);
      $display = new FormattableMarkup($time_diff['past_format'], ['@interval' => $interval]);
      $summary[] = $this->t('Past date: %display', ['%display' => $display]);

      if ($time_diff['refresh']) {
        $refresh_intervals = $this->getRefreshIntervals();
        $summary[] = $this->t('Refresh every @interval', ['@interval' => $refresh_intervals[$time_diff['refresh']]]);
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $date_format = $this->getSetting('date_format');
    $custom_date_format = $this->getSetting('custom_date_format');
    $timezone = $this->getSetting('timezone') ?: NULL;
    $tooltip = $this->getSetting('tooltip');
    $time_diff = $this->getSetting('time_diff');

    if ($time_diff['enabled']) {
      $elements['#attached']['library'][] = 'core/drupal.time-diff';
    }

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'time',
        '#attributes' => [
          // The representation of the date/time as RFC3339 "date-time".
          // @see https://www.ietf.org/rfc/rfc3339.txt
          'datetime' => $this->dateFormatter->format($item->value, 'custom', \DateTimeInterface::RFC3339, $timezone),
          // Show a tooltip on mouse hover as title. When the time is displayed
          // as time difference, it helps the user to read the exact date.
          'title' => $this->dateFormatter->format($item->value, $tooltip['date_format'], $tooltip['custom_date_format'], $timezone, $langcode),
        ],
        '#text' => $this->dateFormatter->format($item->value, $date_format, $custom_date_format, $timezone, $langcode),
        '#cache' => [
          'contexts' => [
            'timezone',
          ],
        ],
      ];
      if ($time_diff['enabled']) {
        $elements[$delta]['#attributes']['class'][] = 'time-diff';
        $settings = [
          'format' => [
            'future' => $time_diff['future_format'],
            'past' => $time_diff['past_format'],
          ],
          'granularity' => $time_diff['granularity'],
          'refresh' => $time_diff['refresh'],
        ];
        $elements[$delta]['#attributes']['data-drupal-time-diff'] = Json::encode($settings);
      }
    }

    return $elements;
  }

  /**
   * Builds the #states key for form elements.
   *
   * @param string[] $path
   *   The remote element path.
   * @param array $conditions
   *   The conditions to be checked.
   *
   * @return array[]
   *   The #states array.
   */
  protected function buildStates(array $path, array $conditions) {
    $path = '[' . implode('][', $path) . ']';
    return [
      'visible' => [
        [
          ":input[name='fields[{$this->fieldDefinition->getName()}][settings_edit_form][settings]$path']" => $conditions,
        ],
      ],
    ];
  }

  /**
   * Returns the refresh interval options for the time difference display.
   *
   * @return array
   *   A list of refresh time intervals.
   */
  protected function getRefreshIntervals() {
    return [
      0 => $this->t('No refresh'),
      1 => $this->formatPlural(1, '1 second', '@count seconds'),
      15 => $this->formatPlural(15, '1 second', '@count seconds'),
      60 => $this->formatPlural(1, '1 minute', '@count minutes'),
      300 => $this->formatPlural(5, '1 minute', '@count minutes'),
      600 => $this->formatPlural(10, '1 minute', '@count minutes'),
    ];
  }

}
