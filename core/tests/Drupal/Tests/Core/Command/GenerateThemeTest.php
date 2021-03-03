<?php

namespace Drupal\Tests\Core\Command;

use Drupal\BuildTests\QuickStart\QuickStartTestBase;
use Drupal\Core\Database\Driver\sqlite\Install\Tasks;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Tests the generate-theme commands.
 *
 * @requires extension pdo_sqlite
 *
 * @group Command
 */
class GenerateThemeTest extends QuickStartTestBase {

  /**
   * The PHP executable path.
   *
   * @var string
   */
  protected $php;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $php_executable_finder = new PhpExecutableFinder();
    $this->php = $php_executable_finder->find();
    $this->copyCodebase();
    $this->executeCommand('COMPOSER_DISCARD_CHANGES=true composer install --no-dev --no-interaction');
    chdir($this->getWorkingPath());
  }

  /**
   * Tests the generate-theme command.
   */
  public function test() {
    if (version_compare(\SQLite3::version()['versionString'], Tasks::SQLITE_MINIMUM_VERSION) < 0) {
      $this->markTestSkipped();
    }

    $install_command = [
      $this->php,
      'core/scripts/drupal',
      'generate-theme',
      'test_custom_theme',
      '--name="Test custom starterkit theme"',
      '--description="Custom theme generated from a starterkit theme"',
    ];
    $process = new Process($install_command, NULL);
    $process->setTimeout(60);
    $result = $process->run();
    $this->assertEquals('', $process->getErrorOutput());
    $this->assertSame(0, $result);

    $theme_path = $this->getWorkspaceDirectory() . '/themes/test_custom_theme';
    $this->assertFileExists($theme_path . '/test_custom_theme.info.yml');

    // Ensure that the generated theme can be installed.
    $this->installQuickStart('minimal');
    $this->formLogin($this->adminUsername, $this->adminPassword);
    $this->visit('/admin/appearance');
    $this->getMink()->assertSession()->pageTextContains('Test custom starterkit');
    $this->getMink()->assertSession()->pageTextContains('Custom theme generated from a starterkit theme');
    $this->getMink()->getSession()->getPage()->clickLink('Install "Test custom starterkit theme" theme');
    $this->getMink()->assertSession()->pageTextContains('The "Test custom starterkit theme" theme has been installed.');
  }

}
