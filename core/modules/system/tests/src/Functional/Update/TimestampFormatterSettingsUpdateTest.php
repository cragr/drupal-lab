<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Assert;

/**
 * Tests the update of timestamp formatter settings in entity view displays.
 *
 * @group system
 * @group legacy
 */
class TimestampFormatterSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.0.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.timestamp-formatter-settings-2921810.php',
    ];
  }

  /**
   * Tests system_post_update_timestamp_formatter().
   *
   * @see system_post_update_timestamp_formatter()
   */
  public function testPostUpdateTimestampFormatter() {
    $config_factory = \Drupal::configFactory();
    $name = 'core.entity_view_display.node.page.default';
    $trail = 'content.field_foo.settings';

    // Check that 'tooltip' and 'time_diff' are missing before update.
    $settings = $config_factory->get($name)->get($trail);
    Assert::assertArrayNotHasKey('tooltip', $settings);
    Assert::assertArrayNotHasKey('time_diff', $settings);

    $this->runUpdates();

    // Check that 'tooltip' and 'time_diff' were created after update.
    $settings = $config_factory->get($name)->get($trail);
    Assert::assertArrayHasKey('tooltip', $settings);
    Assert::assertArrayHasKey('time_diff', $settings);
  }

}
