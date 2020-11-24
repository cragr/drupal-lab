<?php

namespace Drupal\Tests\auto_updates\Functional;

use Drupal\auto_updates_test\Datetime\TestTime;
use Drupal\Tests\auto_updates\Kernel\ReadinessChecker\TestCheckerTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests readiness checkers.
 *
 * @group auto_updates
 */
class ReadinessCheckerTest extends BrowserTestBase {

  use TestCheckerTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user who can view the status report.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $reportViewerUser;

  /**
   * A user how can view the status report and run readiness checkers.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $checkerRunnerUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->reportViewerUser = $this->createUser([
      'administer site configuration',
    ]);
    $this->checkerRunnerUser = $this->createUser([
      'administer site configuration',
      'administer software updates',
    ]);
  }

  /**
   * Tests readiness checkers on status report page.
   */
  public function testReadinessChecksStatusReport():void {
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Disable automated_cron before installing auto_updates. This ensures we
    // are testing that auto_updates runs the checkers when the module itself
    // is installed and they weren't run on cron.
    $this->container->get('module_installer')->uninstall(['automated_cron']);
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test']);

    // If the site is ready for updates, the users will see the same output
    // regardless of whether the user has permission to run updates.
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.');
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.');

    // Confirm a user without the permission to run readiness checks does not
    // have a link to run the checks when the checks need to be run again.
    $this->setFakeTime('+2 days');
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site has not recently checked if it is ready to apply automatic updates. Readiness checks were last run %s ago.');

    // Confirm a user with the permission to run readiness checks does have a
    // link to run the checks when the checks need to be run again.
    $this->drupalLogin($this->checkerRunnerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site has not recently checked if it is ready to apply automatic updates.'
      . ' Readiness checks were last run %s ago. Run readiness checks now.');
    $this->setTestMessages(['OMG 🚒. Your server is on 🔥!']);

    // Run the readiness checks.
    $this->clickLink('Run readiness checks');
    // @todo If coming from the status report page should you be redirected there?
    //   This is how 'Run cron' works.
    $assert->statusCodeEquals(200);
    $assert->addressEquals('/admin/reports/auto_updates');
    $assert->pageTextNotContains('Access denied');
    $assert->pageTextContains('Your site is currently failing readiness checks for automatic updates. It cannot be automatically updated until further action is performed.');
    $assert->pageTextContains('OMG 🚒. Your server is on 🔥!');

    // Confirm the error is displayed on the status report page.
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('1 check failed: OMG 🚒. Your server is on 🔥!');
    // @todo Should we always show when the checks were last run and a link to
    //   run when there is an error?
    // Confirm a user without permission to run the checks sees the same error.
    $this->drupalLogin($this->reportViewerUser);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('1 check failed: OMG 🚒. Your server is on 🔥!');

    $this->setTestMessages(['OMG 🔌. Some one unplugged the server! How is this site even running?']);
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $keyValue */
    $keyValue = $this->container->get('keyvalue.expirable')->get('auto_updates');
    $keyValue->delete('readiness_check_results');
    // Confirm a new message is displayed if the stored messages are deleted.
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('1 check failed: OMG 🔌. Some one unplugged the server! How is this site even running?');
  }

  /**
   * Tests installing a module with a checker before installing auto_updates.
   */
  public function testReadinessCheckAfterInstall(): void {
    $assert = $this->assertSession();
    $this->drupalLogin($this->checkerRunnerUser);

    $this->drupalGet('admin/reports/status');
    $assert->pageTextNotContains('Update readiness checks');

    $this->container->get('module_installer')->install(['auto_updates']);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('Your site is ready for automatic updates.');

    $this->setTestMessages(['😿Oh no! A hacker now owns your files!']);
    $this->container->get('module_installer')->install(['auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('1 check failed: 😿Oh no! A hacker now owns your files!');

    // Confirm that installing a module that does not provide a new checker does
    // not run the checkers on install.
    $this->setTestMessages(['Security has been compromised. "pass123" was a bad password!']);
    $this->container->get('module_installer')->install(['help']);
    $this->drupalGet('admin/reports/status');
    // Confirm that new checker message is not displayed because the checker was
    // not run again.
    $this->assertReadinessReportMatches('1 check failed: 😿Oh no! A hacker now owns your files!');

    // Confirm the new message is displayed after running the checkers manually.
    $this->drupalGet('admin/reports/auto_updates');
    $this->clickLink('run the readiness checks');
    $assert->pageTextContains('Security has been compromised. "pass123" was a bad password!');
    $assert->pageTextNotContains('😿Oh no! A hacker now owns your files!');

    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('1 check failed: Security has been compromised. "pass123" was a bad password!');
  }

  /**
   * Tests that checker message for an uninstalled module is not displayed.
   */
  public function testReadinessCheckerUninstall(): void {
    $assert = $this->assertSession();
    $this->drupalLogin($this->checkerRunnerUser);

    $this->setTestMessages(['😲Your site is running on Commodore 64! Not powerful enough to do updates!']);
    $this->container->get('module_installer')->install(['auto_updates', 'auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $this->assertReadinessReportMatches('1 check failed: 😲Your site is running on Commodore 64! Not powerful enough to do updates!');

    $this->container->get('module_installer')->uninstall(['auto_updates_test']);
    $this->drupalGet('admin/reports/status');
    $assert->pageTextNotContains('1 check failed: 😲Your site is running on Commodore 64! Not powerful enough to do updates!');
  }

  /**
   * Sets a fake time that will be used in the test.
   *
   * @param string $offset
   *   A date/time offset string.
   */
  private function setFakeTime(string $offset): void {
    $fake_delay = (new \DateTime())->modify($offset)->format(TestTime::TIME_FORMAT);
    $this->container->get('state')->set(TestTime::STATE_KEY, $fake_delay);
  }

  /**
   * Asserts status report readiness report item matches a format.
   *
   * @param string $format
   *   The string to match.
   */
  private function assertReadinessReportMatches(string $format): void {
    // Prefix the expected format with the item title which does not change.
    $format = "Update readiness checks $format";
    $text = $this->getSession()->getPage()->find(
      'css',
      'details.system-status-report__entry:contains("Update readiness checks")'
    )->getText();
    $this->assertStringMatchesFormat($format, $text);
  }

}
