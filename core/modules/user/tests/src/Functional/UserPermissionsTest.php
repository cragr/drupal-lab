<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Verify that role permissions can be added and removed via the permissions
 * page.
 *
 * @group user
 */
class UserPermissionsTest extends BrowserTestBase {

  /**
   * User with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * User's role ID.
   *
   * @var string
   */
  protected $rid;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer permissions',
      'access user profiles',
      'administer site configuration',
      'administer modules',
      'administer account settings',
    ]);

    // Find the new role ID.
    $all_rids = $this->adminUser->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    $this->rid = reset($all_rids);
  }

  /**
   * Test changing user permissions through the permissions page.
   */
  public function testUserPermissionChanges() {
    $permissions_hash_generator = $this->container->get('user_permissions_hash_generator');

    $storage = $this->container->get('entity_type.manager')->getStorage('user_role');
    $storage->resetCache();

    $this->drupalLogin($this->adminUser);
    $rid = $this->rid;
    $account = $this->adminUser;
    $previous_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertSame($previous_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));

    // Add a permission.
    $this->assertFalse($account->hasPermission('administer users'), 'User does not have "administer users" permission.');
    $edit = [];
    $edit[$rid . '[administer users]'] = TRUE;
    $this->drupalPostForm('admin/people/permissions', $edit, 'Save permissions');
    $this->assertText('The changes have been saved.');
    $storage->resetCache();
    $this->assertTrue($account->hasPermission('administer users'), 'User now has "administer users" permission.');
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertSame($current_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));
    $this->assertNotEquals($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');
    $previous_permissions_hash = $current_permissions_hash;

    // Remove a permission.
    $this->assertTrue($account->hasPermission('access user profiles'), 'User has "access user profiles" permission.');
    $edit = [];
    $edit[$rid . '[access user profiles]'] = FALSE;
    $this->drupalPostForm('admin/people/permissions', $edit, 'Save permissions');
    $this->assertText('The changes have been saved.');
    $storage->resetCache();
    $this->assertFalse($account->hasPermission('access user profiles'), 'User no longer has "access user profiles" permission.');
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertSame($current_permissions_hash, $permissions_hash_generator->generate($this->loggedInUser));
    $this->assertNotEquals($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');

    // Ensure that the admin role doesn't have any checkboxes.
    $this->drupalGet('admin/people/permissions');
    foreach (array_keys($this->container->get('user.permissions')->getPermissions()) as $permission) {
      $this->assertSession()->checkboxChecked('administrator[' . $permission . ']');
      $this->assertSession()->fieldDisabled('administrator[' . $permission . ']');
    }
  }

  /**
   * Verify proper permission changes by user_role_change_permissions().
   */
  public function testUserRoleChangePermissions() {
    $permissions_hash_generator = $this->container->get('user_permissions_hash_generator');

    $rid = $this->rid;
    $account = $this->adminUser;
    $previous_permissions_hash = $permissions_hash_generator->generate($account);

    // Verify current permissions.
    $this->assertFalse($account->hasPermission('administer users'), 'User does not have "administer users" permission.');
    $this->assertTrue($account->hasPermission('access user profiles'), 'User has "access user profiles" permission.');
    $this->assertTrue($account->hasPermission('administer site configuration'), 'User has "administer site configuration" permission.');

    // Change permissions.
    $permissions = [
      'administer users' => 1,
      'access user profiles' => 0,
    ];
    user_role_change_permissions($rid, $permissions);

    // Verify proper permission changes.
    $this->assertTrue($account->hasPermission('administer users'), 'User now has "administer users" permission.');
    $this->assertFalse($account->hasPermission('access user profiles'), 'User no longer has "access user profiles" permission.');
    $this->assertTrue($account->hasPermission('administer site configuration'), 'User still has "administer site configuration" permission.');

    // Verify the permissions hash has changed.
    $current_permissions_hash = $permissions_hash_generator->generate($account);
    $this->assertNotEquals($previous_permissions_hash, $current_permissions_hash, 'Permissions hash has changed.');
  }

  /**
   * Verify 'access content' is listed in the correct location.
   */
  public function testAccessContentPermission() {
    $this->drupalLogin($this->adminUser);

    // When Node is not installed the 'access content' permission is listed next
    // to 'access site reports'.
    $this->drupalGet('admin/people/permissions');
    $next_row = $this->xpath('//tr[@data-drupal-selector=\'edit-permissions-access-content\']/following-sibling::tr[1]');
    $this->assertEqual('edit-permissions-access-site-reports', $next_row[0]->getAttribute('data-drupal-selector'));

    // When Node is installed the 'access content' permission is listed next to
    // to 'view own unpublished content'.
    \Drupal::service('module_installer')->install(['node']);
    $this->drupalGet('admin/people/permissions');
    $next_row = $this->xpath('//tr[@data-drupal-selector=\'edit-permissions-access-content\']/following-sibling::tr[1]');
    $this->assertEqual('edit-permissions-view-own-unpublished-content', $next_row[0]->getAttribute('data-drupal-selector'));
  }

}
