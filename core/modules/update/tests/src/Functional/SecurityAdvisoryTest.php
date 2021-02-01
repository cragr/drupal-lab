<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\update_test\AdvisoriesTestHttpClient;

/**
 * Tests of security advisories functionality.
 *
 * @group update
 */
class SecurityAdvisoryTest extends BrowserTestBase {

  use AssertMailTrait;
  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'update',
    'aaa_update_test',
    'update_test',
  ];

  /**
   * A user with permission to administer site configuration and updates.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * A working test PSA endpoint.
   *
   * @var string
   */
  protected $workingEndpoint;

  /**
   * A working test PSA endpoint that has 1 more item than $workingEndpoint.
   *
   * @var string
   */
  protected $workingEndpointPlus1;

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
    // Alter the 'aaa_update_test' module to use the 'aaa_update_project'
    // project name.  This ensures that for an extension where the 'name' and
    // the 'project' properties do not match, 'project' is used for matching
    // 'project' in the JSON feed.
    $system_info = [
      'aaa_update_test' => [
        'project' => 'aaa_update_project',
        'version' => '8.x-1.1',
        'hidden' => FALSE,
      ],
      'bbb_update_test' => [
        'project' => 'bbb_update_project',
        'version' => '8.x-1.1',
        'hidden' => FALSE,
      ],
    ];
    $this->config('update_test.settings')->set('system_info', $system_info)->save();
    $this->user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer software updates',
    ]);
    $this->drupalLogin($this->user);
    $fixtures_path = $this->baseUrl . '/core/modules/update/tests/fixtures/psa_feed';
    $this->workingEndpoint = $this->buildUrl('/update-test-json/valid');
    $this->workingEndpointPlus1 = $this->buildUrl('/update-test-json/valid_plus1');
    $this->nonWorkingEndpoint = $this->buildUrl('/update-test-json/missing');
    $this->invalidJsonEndpoint = "$fixtures_path/invalid.json";

    $this->tempStore = $this->container->get('keyvalue.expirable')->get('update');
  }

  /**
   * Tests that a security advisory is displayed.
   */
  public function testPsa(): void {
    $assert = $this->assertSession();
    // Setup test PSA endpoint.
    AdvisoriesTestHttpClient::setTestEndpoint($this->workingEndpoint);
    $this->drupalGet(Url::fromRoute('system.admin'));
    $assert->linkExists('Critical Release - SA-2019-02-19');
    $assert->linkExists('Critical Release - PSA-Really Old');
    $assert->linkExists('AAA Update Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02');
    $assert->linkExists('BBB Update project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02');

    // Test site status report.
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert->linkExists('Critical Release - SA-2019-02-19');
    $assert->linkExists('Critical Release - PSA-Really Old');
    $assert->linkExists('AAA Update Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02');
    $assert->linkExists('BBB Update project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02');

    // Test cache.
    AdvisoriesTestHttpClient::setTestEndpoint($this->nonWorkingEndpoint);
    $this->drupalGet(Url::fromRoute('system.admin'));
    $assert->linkExists('Critical Release - SA-2019-02-19');
    $assert->linkExists('Critical Release - PSA-Really Old');
    $assert->linkExists('AAA Update Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02');
    $assert->linkExists('BBB Update project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02');

    // Tests transmit errors with a JSON endpoint.
    $this->tempStore->delete('advisories_response');
    $this->drupalGet(Url::fromRoute('system.admin'));
    $assert->linkNotExists('Critical Release - SA-2019-02-19');

    // Test that the site status report displays an error.
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert->pageTextContains('Failed to fetch security advisory data:');
    $assert->linkNotExists('Critical Release - SA-2019-02-19');

    // Test a PSA endpoint that returns invalid JSON.
    AdvisoriesTestHttpClient::setTestEndpoint($this->invalidJsonEndpoint);
    $this->tempStore->delete('advisories_response');
    // On admin pages no message should be displayed if the feed is malformed.
    $this->drupalGet(Url::fromRoute('system.admin'));
    $assert->linkNotExists('Critical Release - PSA-2019-02-19');
    // On the status report there should be no announcements section.
    $this->drupalGet(Url::fromRoute('system.status'));
    $assert->pageTextNotContains('Failed to fetch security advisory data:');
    $assert->linkNotExists('Critical Release - PSA-2019-02-19');
  }

  /**
   * Tests sending security advisory email notifications.
   */
  public function testPsaMail(): void {
    // Set up test PSA endpoint.
    AdvisoriesTestHttpClient::setTestEndpoint($this->workingEndpoint);
    $this->createUser([], 'GracieDog');
    // Setup a default destination email address.
    $this->config('update.settings')
      ->set('notification.emails', ['admin@example.com', 'GracieDog@example.com'])
      ->save();

    // Confirm that security advisory cache does not exist.
    $this->assertNull($this->tempStore->get('advisories_response'));

    // Test security advisories on admin pages.
    $this->drupalGet(Url::fromRoute('system.admin'));
    $this->assertSession()->linkExists('Critical Release - SA-2019-02-19');
    // Confirm that the security advisory cache has been set.
    $this->assertNotEmpty($this->tempStore->get('advisories_response'));

    // Email should be sent.
    $this->cronRun();
    $this->assertAdvisoryEmailCount(2);
    $this->assertMailString('subject', '4 urgent security announcements require your attention', 1);
    $this->assertMailString('body', 'Critical Release - SA-2019-02-19', 1);
    $this->assertMailString('body', 'Critical Release - PSA-Really Old', 1);
    $this->assertMailString('body', 'AAA Update Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02', 1);
    $this->assertMailString('body', 'AAA Update Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02', 1);
    $this->assertMailString('body', 'To see all public service announcements, visit https://www.drupal.org/security/psa', 1);
    $this->assertMailString('body', 'To change how you are notified, you may configure email notifications', 1);
    $this->assertMailString('body', $this->baseUrl . '/admin/reports/updates/settings', 1);

    // Deleting the security advisory cache will not result in another email if
    // the messages have not changed.
    // @todo Replace deleting the cache directly in the test with faking a later
    //   date and letting the cache item expire in
    //   https://www.drupal.org/node/3113971.
    $this->tempStore->delete('advisories_response');
    $this->container->get('state')->set('system.test_mail_collector', []);
    $this->cronRun();
    $this->assertAdvisoryEmailCount(0);

    // Deleting the security advisory tempstore item will result in another
    // email if the messages have changed.
    $this->tempStore->delete('advisories_response');
    $this->container->get('state')->set('system.test_mail_collector', []);
    AdvisoriesTestHttpClient::setTestEndpoint($this->workingEndpointPlus1);
    $this->cronRun();
    $this->assertAdvisoryEmailCount(2);
    $this->assertMailString('subject', '5 urgent security announcements require your attention', 1);
    $this->assertMailString('body', 'Critical Release - SA-2019-02-19', 1);
    $this->assertMailString('body', 'Critical Release - PSA-Really Old', 1);
    $this->assertMailString('body', 'AAA Update Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02', 1);
    $this->assertMailString('body', 'AAA Update Project - Moderately critical - Access bypass - SA-CONTRIB-2019-02-02', 1);
    $this->assertMailString('body', 'Critical Release - PSA because 2020', 1);
  }

  /**
   * Tests that an email is not sent when the PSA JSON is invalid.
   */
  public function testInvalidJsonEmail(): void {
    // Setup a default destination email address.
    $this->config('update.settings')
      ->set('notification.emails', ['admin@example.com'])
      ->save();
    AdvisoriesTestHttpClient::setTestEndpoint($this->invalidJsonEndpoint);
    $this->tempStore->delete('advisories_response');
    $this->cronRun();
    $this->assertAdvisoryEmailCount(0);
  }

  /**
   * Assert the count of the 'update_advisory_notify' emails during the test.
   *
   * @param int $expected_count
   *   The expected count.
   */
  protected function assertAdvisoryEmailCount(int $expected_count): void {
    $this->assertCount($expected_count, $this->getMails(['id' => 'update_advisory_notify']));
  }

}
