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
    $config = $this->container->get('config.factory')->get('update.settings');
    $this->assertNull($config->get('advisories'));

    $this->runUpdates();

    $config = \Drupal::configFactory()->get('update.settings');
    $this->assertSame(
      [
        'interval_hours' => 12,
      ],
      $config->get('advisories')
    );
  }

}
