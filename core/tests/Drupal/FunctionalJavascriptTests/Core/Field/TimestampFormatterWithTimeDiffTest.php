<?php

namespace Drupal\FunctionalJavascriptTests\Core\Field;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the 'timestamp' formatter when is used with time difference setting.
 *
 * @group Field
 */
class TimestampFormatterWithTimeDiffTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'time_field',
      'type' => 'timestamp',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'time_field',
      'label' => $this->randomString(),
    ])->save();
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $display->setComponent('time_field', [
      'type' => 'timestamp',
      'settings' => [
        'time_diff' => [
          'enabled' => TRUE,
          'future_format' => '@interval hence',
          'past_format' => '@interval ago',
          'granularity' => 2,
          'refresh' => 1,
        ],
      ],
    ])->setStatus(TRUE)->save();

    $account = $this->createUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests the 'timestamp' formatter when is used with time difference setting.
   */
  public function testTimestampFormatterWithTimeDiff() {
    $entity = EntityTest::create([
      'type' => 'entity_test',
      'name' => $this->randomString(),
      'time_field' => $this->container->get('datetime.time')->getRequestTime(),
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl());

    // Unit testing Drupal.dateFormatter.formatDiff(). Not using @dataProvider
    // mechanism here in order to avoid installing the site for each case.
    foreach ($this->getFormatDiffTestCases() as $case) {
      $from = \DateTime::createFromFormat(\DateTime::RFC3339, $case['from'])->getTimestamp() * 1000;
      $to = \DateTime::createFromFormat(\DateTime::RFC3339, $case['to'])->getTimestamp() * 1000;
      $options = json_encode($case['options']);
      $expected_value = json_encode($case['expected_value']);
      $expected_formatted_value = $case['expected_formatted_value'];

      // Test the returned value.
      $this->assertJsCondition("JSON.stringify(Drupal.dateFormatter.formatDiff($from, $to, $options).value) === '$expected_value'");
      // Test the returned formatted value.
      $this->assertJsCondition("Drupal.dateFormatter.formatDiff($from, $to, $options).formatted === '$expected_formatted_value'");
    }

    // Unit testing Drupal.timestampAsTimeDiff.refreshInterval(). Not using
    // @dataProvider mechanism here in order to avoid reinstalling the site for
    // each case.
    foreach ($this->getTimeoutTestCases() as $case) {
      $interval = json_encode($case['time_diff']);
      $this->assertJsCondition("Drupal.timestampAsTimeDiff.refreshInterval($interval, {$case['configured_refresh_interval']}, {$case['granularity']}) === {$case['computed_refresh_interval']}");
    }

    // Test the UI.
    $time_element = $this->getSession()->getPage()->find('css', 'time');

    $time_diff = $time_element->getText();
    list($seconds_value,) = explode(' ', $time_diff, 2);

    // Wait at least 1 second + 1 millisecond to make sure that the last time
    // difference value has been refreshed.
    $this->assertJsCondition("jQuery('time').text() != '$time_diff'", 1001);
    $time_diff = $time_element->getText();
    list($new_seconds_value,) = explode(' ', $time_diff, 2);
    $this->assertGreaterThan($seconds_value, $new_seconds_value);

    // Once again.
    $this->assertJsCondition("jQuery('time').text() != '$time_diff'", 1001);
    $time_diff = $time_element->getText();
    $seconds_value = $new_seconds_value;
    list($new_seconds_value,) = explode(' ', $time_diff, 2);
    $this->assertGreaterThan($seconds_value, $new_seconds_value);
  }

  /**
   * Provides test cases for unit testing Drupal.dateFormatter.formatDiff().
   *
   * @return array
   *   A list of of test cases, each representing parameters to be passed to the
   *   javascript function.
   */
  protected function getFormatDiffTestCases() {
    return [
      'normal, granularity: 2' => [
        'from' => '2010-02-11T10:00:00+00:00',
        'to' => '2010-02-16T14:00:00+00:00',
        'options' => [
          'granularity' => 2,
        ],
        'expected_value' => [
          'day' => 5,
          'hour' => 4,
        ],
        'expected_formatted_value' => '5 days 4 hours',
      ],
      'inverted, strict' => [
        'from' => '2010-02-16T14:00:00+00:00',
        'to' => '2010-02-11T10:00:00+00:00',
        'options' => [
          'granularity' => 2,
        ],
        'expected_value' => [
          'second' => 0,
        ],
        'expected_formatted_value' => '0 seconds',
      ],
      'inverted, strict (strict passed explicitly)' => [
        'from' => '2010-02-16T14:00:00+00:00',
        'to' => '2010-02-11T10:00:00+00:00',
        'options' => [
          'granularity' => 2,
          'strict' => TRUE,
        ],
        'expected_value' => [
          'second' => 0,
        ],
        'expected_formatted_value' => '0 seconds',
      ],
      'inverted, non-strict' => [
        'from' => '2010-02-16T14:00:00+00:00',
        'to' => '2010-02-11T10:00:00+00:00',
        'options' => [
          'granularity' => 2,
          'strict' => FALSE,
        ],
        'expected_value' => [
          'day' => 5,
          'hour' => 4,
        ],
        'expected_formatted_value' => '5 days 4 hours',
      ],
      'normal, max granularity' => [
        'from' => '2010-02-02T10:30:45+00:00',
        'to' => '2011-06-24T11:37:02+00:00',
        'options' => [
          'granularity' => 7,
        ],
        'expected_value' => [
          'year' => 1,
          'month' => 4,
          'week' => 3,
          'day' => 1,
          'hour' => 1,
          'minute' => 6,
          'second' => 17,
        ],
        'expected_formatted_value' => '1 year 4 months 3 weeks 1 day 1 hour 6 minutes 17 seconds',
      ],
      "'1 hour 0 minutes 1 second' is '1 hour'" => [
        'from' => '2010-02-02T10:30:45+00:00',
        'to' => '2010-02-02T11:30:46+00:00',
        'options' => [
          'granularity' => 3,
        ],
        'expected_value' => [
          'hour' => 1,
        ],
        'expected_formatted_value' => '1 hour',
      ],
      "'1 hour 0 minutes' is '1 hour'" => [
        'from' => '2010-02-02T10:30:45+00:00',
        'to' => '2010-02-02T11:30:45+00:00',
        'options' => [
          'granularity' => 2,
        ],
        'expected_value' => [
          'hour' => 1,
        ],
        'expected_formatted_value' => '1 hour',
      ],
    ];
  }

  /**
   * Provides test cases for unit testing Drupal.timestampAsTimeDiff.timeout().
   *
   * @return array
   *   A list  of of test cases, each representing parameters to be passed to the
   *   javascript function.
   */
  protected function getTimeoutTestCases() {
    return [
      'passed timeout is not altered' => [
        'time_diff' => [
          'hour' => 11,
          'minute' => 10,
          'second' => 30,
        ],
        'configured_refresh_interval'  => 10,
        'granularity' => 3,
        'computed_refresh_interval' => 10,
      ],
      'timeout lower than the lowest interval part' => [
        'time_diff' => [
          'hour' => 11,
          'minute' => 10,
        ],
        'configured_refresh_interval'  => 59,
        'granularity' => 2,
        'computed_refresh_interval' => 60,
      ],
      'timeout with number of parts lower than the granularity' => [
        'time_diff' => [
          'hour' => 1,
          'minute' => 0,
        ],
        'configured_refresh_interval'  => 10,
        'granularity' => 2,
        'computed_refresh_interval' => 60,
      ],
      'big refresh interval' => [
        'time_diff' => [
          'minute' => 3,
          'second' => 30,
        ],
        'configured_refresh_interval'  => 1000,
        'granularity' => 1,
        'computed_refresh_interval' => 1000,
      ],
    ];
  }

}
