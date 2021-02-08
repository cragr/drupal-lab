<?php

namespace Drupal\Tests\system\Functional\SecurityAdvisories;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\advisory_feed_test\AdvisoriesTestHttpClient;

/**
 * Tests of security advisories functionality.
 *
 * @group update
 */
class SecurityAdvisoryTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'generic_module1_test',
    'advisory_feed_test',
  ];

  /**
   * A user with permission to administer site configuration and updates.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A test PSA endpoint that will display both PSA and non-PSA advisories.
   *
   * @var string
   */
  protected $workingEndpointMixed;

  /**
   * A test PSA endpoint that has 1 more item than $workingEndpoint.
   *
   * @var string
   */
  protected $workingEndpointPlus1;

  /**
   * A test PSA endpoint that will only display PSA advisories.
   *
   * @var string
   */
  protected $workingEndpointPsaOnly;

  /**
   * A test PSA endpoint that will only display non-PSA advisories.
   *
   * @var string
   */
  protected $workingEndpointNonPsaOnly;

  /**
   * A non-working test PSA endpoint.
   *
   * @var string
   */
  protected $nonWorkingEndpoint;

  /**
   * A test PSA endpoint that returns invalid JSON.
   *
   * @var string
   */
  protected $invalidJsonEndpoint;

  /**
   * The key/value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer software updates',
    ]);
    $this->drupalLogin($this->user);
    $fixtures_path = $this->baseUrl . '/core/modules/system/tests/fixtures/psa_feed';
    $this->workingEndpointMixed = $this->buildUrl('/advisory-feed-json/valid-mixed');
    $this->workingEndpointPsaOnly = $this->buildUrl('/advisory-feed-json/valid-psa-only');
    $this->workingEndpointNonPsaOnly = $this->buildUrl('/advisory-feed-json/valid-non-psa-only');
    $this->workingEndpointPlus1 = $this->buildUrl('/advisory-feed-json/valid_plus1');
    $this->nonWorkingEndpoint = $this->buildUrl('/advisory-feed-json/missing');
    $this->invalidJsonEndpoint = "$fixtures_path/invalid.json";

    $this->tempStore = $this->container->get('keyvalue.expirable')->get('system');
  }

  /**
   * Tests that a security advisory is displayed.
   */
  public function testPsa(): void {
    $assert = $this->assertSession();
    // Setup test PSA endpoint.
    AdvisoriesTestHttpClient::setTestEndpoint($this->workingEndpointMixed);
    $advisory_links = [
      'Critical Release - SA-2019-02-19',
      'Critical Release - PSA-Really Old',
      // The info for the test modules 'generic_module1_test' and
      // 'generic_module2_test' are altered for this test so match the items in
      // the test json feeds.
      // @see advisory_feed_test_system_info_alter()
      'Generic Module1 Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
      'Generic Module2 project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
    ];
    // Confirm that links are not displayed if they are enabled.
    $this->config('system.advisories')->set('enabled', FALSE)->save();
    $this->assertAdvisoriesNotDisplayed($advisory_links);
    $this->config('system.advisories')->set('enabled', TRUE)->save();
    // If both PSA and non-PSA advisories are displayed they should be displayed
    // as errors.
    $this->assertAdminPageLinks($advisory_links, REQUIREMENT_ERROR);
    $this->assertStatusReportLinks($advisory_links, REQUIREMENT_ERROR);

    // Confirm that a user without the correct permission will not see the
    // advisories on admin pages.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
    ]));
    $this->assertAdvisoriesNotDisplayed($advisory_links, ['system.admin']);

    // Log back in with user with permission to see the advisories.
    $this->drupalLogin($this->user);
    // Test cache.
    AdvisoriesTestHttpClient::setTestEndpoint($this->nonWorkingEndpoint);
    $this->assertAdminPageLinks($advisory_links, REQUIREMENT_ERROR);
    $this->assertStatusReportLinks($advisory_links, REQUIREMENT_ERROR);

    // Tests transmit errors with a JSON endpoint.
    $this->tempStore->delete('advisories_response');
    $this->drupalGet(Url::fromRoute('system.admin'));
    $assert->linkNotExists('Critical Release - SA-2019-02-19');

    // Test that the site status report displays an error.
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert->pageTextContains('Failed to fetch security advisory data:');
    $assert->linkNotExists('Critical Release - SA-2019-02-19');

    // Test a PSA endpoint that returns invalid JSON.
    AdvisoriesTestHttpClient::setTestEndpoint($this->invalidJsonEndpoint, TRUE);
    // On admin pages no message should be displayed if the feed is malformed.
    $this->drupalGet(Url::fromRoute('system.admin'));
    $assert->linkNotExists('Critical Release - PSA-2019-02-19');
    // On the status report there should be no announcements section.
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert->pageTextNotContains('Failed to fetch security advisory data:');
    $assert->linkNotExists('Critical Release - PSA-2019-02-19');

    AdvisoriesTestHttpClient::setTestEndpoint($this->workingEndpointPsaOnly, TRUE);
    $advisory_links = [
      'Critical Release - PSA-Really Old',
      'Generic Module2 project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
    ];
    // If only PSA advisories are displayed they should be displayed as
    // warnings.
    $this->assertAdminPageLinks($advisory_links, REQUIREMENT_WARNING);
    $this->assertStatusReportLinks($advisory_links, REQUIREMENT_WARNING);

    AdvisoriesTestHttpClient::setTestEndpoint($this->workingEndpointNonPsaOnly, TRUE);
    $advisory_links = [
      'Critical Release - SA-2019-02-19',
      'Generic Module1 Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02',
    ];
    // If only non-PSA advisories are displayed they should be displayed as
    // errors.
    $this->assertAdminPageLinks($advisory_links, REQUIREMENT_ERROR);
    $this->assertStatusReportLinks($advisory_links, REQUIREMENT_ERROR);

    // Confirm that advisory fetching can be displayed after enabled.
    $this->config('system.advisories')->set('enabled', FALSE)->save();
    $this->assertAdvisoriesNotDisplayed($advisory_links);
  }

  /**
   * Asserts the correct links appear on an admin page.
   *
   * @param string[] $expected_link_texts
   *   The expected links' text.
   * @param int $error_or_warning
   *   Whether the error is a warning or an error.
   */
  private function assertAdminPageLinks(array $expected_link_texts, int $error_or_warning) {
    $assert = $this->assertSession();
    $this->drupalGet(Url::fromRoute('system.admin'));
    if ($error_or_warning === REQUIREMENT_ERROR) {
      $assert->pageTextContainsOnce('Error message');
      $assert->pageTextNotContains('Warning message');
    }
    else {
      $assert->pageTextNotContains('Error message');
      $assert->pageTextContainsOnce('Warning message');
    }
    foreach ($expected_link_texts as $expected_link_text) {
      $assert->linkExists($expected_link_text);
    }
  }

  /**
   * Asserts the correct links appear on the status report page.
   *
   * @param string[] $expected_link_texts
   *   The expected links' text.
   * @param int $error_or_warning
   *   Whether the error is a warning or an error.
   */
  private function assertStatusReportLinks(array $expected_link_texts, int $error_or_warning) {
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert = $this->assertSession();
    $selector = 'h3#' . ($error_or_warning === REQUIREMENT_ERROR ? 'error' : 'warning')
      . ' ~ details.system-status-report__entry:contains("Critical security announcements")';
    $assert->elementExists('css', $selector);
    foreach ($expected_link_texts as $expected_link_text) {
      $assert->linkExists($expected_link_text);
    }
  }

  /**
   * Assert the links are not shown on the admin pages.
   *
   * @param array $links
   *   The advisory links.
   */
  private function assertAdvisoriesNotDisplayed(array $links, array $routes = ['system.admin', 'system.status']): void {
    foreach ($routes as $route) {
      $this->drupalGet(Url::fromRoute($route));
      $this->assertSession()->statusCodeEquals(200);
      foreach ($links as $link) {
        $this->assertSession()->linkNotExists($link, "'$link' not displayed on route '$route'.");
      }
    }
  }

}
