<?php

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;

/**
 * Tests that update hooks are properly run.
 *
 * @group Update
 */
class UpdateSchemaTest extends BrowserTestBase {

  use RequirementsPageTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_schema'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The update URL.
   *
   * @var string
   */
  protected $updateUrl;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    require_once $this->root . '/core/includes/update.inc';
    $this->user = $this->drupalCreateUser([
      'administer software updates',
      'access site in maintenance mode',
    ]);
    $this->updateUrl = Url::fromRoute('system.db_update');
  }

  /**
   * Tests that update hooks are properly run.
   */
  public function testUpdateHooks() {
    $connection = Database::getConnection();

    // Verify that the 8000 schema is in place.
    $this->assertEqual(8000, drupal_get_installed_schema_version('update_test_schema'));
    $this->assertFalse($connection->schema()->indexExists('update_test_schema_table', 'test'), 'Version 8000 of the update_test_schema module is installed.');

    // Increment the schema version.
    \Drupal::state()->set('update_test_schema_version', 8001);

    $this->drupalLogin($this->user);
    $this->drupalGet($this->updateUrl, ['external' => TRUE]);
    $this->updateRequirementsProblem();
    $this->clickLink(t('Continue'));
    $this->assertRaw('Schema version 8001.');
    // Run the update hooks.
    $this->clickLink(t('Apply pending updates'));
    $this->checkForMetaRefresh();

    // Ensure schema has changed.
    $this->assertEqual(8001, drupal_get_installed_schema_version('update_test_schema', TRUE));
    // Ensure the index was added for column a.
    $this->assertTrue($connection->schema()->indexExists('update_test_schema_table', 'test'), 'Version 8001 of the update_test_schema module is installed.');
  }

}
