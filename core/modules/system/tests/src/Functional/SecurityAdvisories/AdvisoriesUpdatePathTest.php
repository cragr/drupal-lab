<?php

namespace Drupal\Tests\update\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests advisories settings update path.
 *
 * @group update
 */
class AdvisoriesUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      dirname(__DIR__, 4) . '/system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests advisories settings update path.
   */
  public function testUpdatePath() {
    /** @var \Drupal\Core\Config\Config $config */
    $this->assertNull($this->config('update.settings')->get('advisories'));

    $this->runUpdates();

    $this->assertSame(
      [
        'interval_hours' => 12,
      ],
      $this->config('update.settings')->get('advisories')
    );
  }

}
