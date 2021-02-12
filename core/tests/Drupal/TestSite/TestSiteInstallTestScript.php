<?php

namespace Drupal\TestSite;

/**
 * Setup file used by TestSiteApplicationTest.
 *
 * @see \Drupal\Tests\Scripts\TestSiteApplicationTest
 */
class TestSiteInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install(['test_page_test', 'advisory_feed_test']);
    // Make sure tests do not make service advisory fetching unless the test
    // opts in.
    \Drupal::configFactory()
      ->getEditable('system.advisories')
      ->set('enabled', FALSE)
      ->save();
  }

}
