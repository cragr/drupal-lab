<?php

namespace Drupal\Tests\tour\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Confirms tour tip `selector` config was updated properly.
 *
 * @group Update
 * @see tour_update_9200()
 */
class TourTipSelectorConfigUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Confirm that tour_update_9200() populates the `selector` property.
   *
   * Joyride-based tours used the `data-id` and `data-class` attributes to
   * associate a tour tip with an element. This was changed to a `selector`
   * property. Existing tours are refactored to use this new property via
   * tour_update_9200(), and this test confirms it is done properly.
   */
  public function testSelectorUpdate() {
    $this->container->get('module_installer')->install(['tour', 'tour_test']);

    $legacy_tour_config = $this->container->get('config.factory')->get('tour.tour.tour-test-legacy');
    $tips = $legacy_tour_config->get('tips');

    // Confirm the existing tours do not have the `selector` property.
    $this->assertFalse(isset($tips['tour-test-legacy-1']['selector']));
    $this->assertFalse(isset($tips['tour-test-legacy-6']['selector']));

    // Confirm the value of the tour-test-1 `data-id` attribute.
    $this->assertEquals('tour-test-1', $tips['tour-test-legacy-1']['attributes']['data-id']);

    // Confirm the value of the tour-test-5 `data-class` attribute.
    $this->assertEquals('tour-test-5', $tips['tour-test-legacy-6']['attributes']['data-class']);

    $this->runUpdates();

    $updated_legacy_tour_config = $this->container->get('config.factory')->get('tour.tour.tour-test-legacy');
    $updated_tips = $updated_legacy_tour_config->get('tips');

    // Confirm that tour-test-1 uses `selector` instead of `data-id`.
    $this->assertEquals('#tour-test-1', $updated_tips['tour-test-legacy-1']['selector']);
    $this->assertFalse(isset($updated_tips['tour-test-legacy-1']['attributes']['data-id']));

    // Confirm that tour-test-5 uses `selector` instead of `data-class`.
    $this->assertEquals('.tour-test-5', $updated_tips['tour-test-legacy-6']['selector']);
    $this->assertFalse(isset($updated_tips['tour-test-legacy-6']['attributes']['data-class']));
  }

}
