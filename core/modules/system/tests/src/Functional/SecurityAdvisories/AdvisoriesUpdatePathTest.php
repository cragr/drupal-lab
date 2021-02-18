<?php

namespace Drupal\Tests\system\Functional\SecurityAdvisories;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests advisories settings update path.
 *
 * @group system
 */
class AdvisoriesUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      dirname(__DIR__, 3) . '/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests advisories settings update path.
   */
  public function testUpdatePath(): void {
    $this->assertNull($this->config('system.advisories')->get('interval_hours'));
    $this->assertNull($this->config('system.advisories')->get('enabled'));

    $this->runUpdates();

    $this->assertSame(12, $this->config('system.advisories')->get('interval_hours'));
    $this->assertSame(TRUE, $this->config('system.advisories')->get('enabled'));
  }

}
