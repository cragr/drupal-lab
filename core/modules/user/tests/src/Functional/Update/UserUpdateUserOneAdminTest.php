<?php

namespace Drupal\Tests\user\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests user 1 receiving the administrator role upgrade path.
 *
 * @group Update
 */
class UserUpdateUserOneAdminTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that nothing happens if user 1 already have an administrator role.
   */
  public function testUserOneHasAdminRole() {
    $this->assertTrue($this->userOneHasAdminRole());
    $this->runUpdates();
    $this->assertTrue($this->userOneHasAdminRole());
  }

  /**
   * Tests that user 1 gets the admin role assigned.
   */
  public function testUserOneGetsAdminRoleAssigned() {
    $user = User::load(1);
    $user->removeRole('administrator');
    $user->save();

    $this->assertFalse($this->userOneHasAdminRole());
    $this->runUpdates();
    $this->assertTrue($this->userOneHasAdminRole());
  }

  /**
   * Tests that user 1 gets the new administrator role assigned.
   */
  public function testUserOneGetsNewAdminRoleAssigned() {
    $user = User::load(1);
    $user->removeRole('administrator');
    $user->save();

    $role = Role::load('administrator');
    $role->delete();

    $this->assertFalse($this->userOneHasAdminRole());
    $this->runUpdates();

    $role = Role::load('administrator');
    $this->assertTrue($role->isAdmin());

    $user = User::load(1);
    $this->assertTrue(in_array('administrator', $user->getRoles()));
  }

  /**
   * Data provider for testUserOneGetsNewSpecialAdminRoleAssigned()
   */
  public function providerUserOneGetsNewSpecialAdminRoleAssigned() {
    return [
      [TRUE, TRUE],
      [TRUE, FALSE],
      [FALSE, TRUE],
      [FALSE, FALSE],
    ];
  }

  /**
   * Tests that user 1 gets the new special administrator role assigned.
   *
   * @dataProvider providerUserOneGetsNewSpecialAdminRoleAssigned
   */
  public function testUserOneGetsNewSpecialAdminRoleAssigned($has_role_with_id_administrator, $role_is_blocked) {
    $user = User::load(1);
    if ($has_role_with_id_administrator) {
      $user->removeRole('administrator');
    }
    if ($role_is_blocked) {
      $user->block();
    }
    $user->save();

    $role = Role::load('administrator');
    $role->setIsAdmin(FALSE);
    $role->save();

    $this->assertFalse($this->userOneHasAdminRole());
    $this->runUpdates();

    if ($role_is_blocked) {
      $this->assertTrue($user->isBlocked());
    }
    else {
      $this->assertTrue($user->isActive());
    }

    $role = Role::load('administrator');
    $this->assertFalse($role->isAdmin());

    $role = Role::load('auto_generated_admin_9_2_0');
    $this->assertTrue($role->isAdmin());

    $user = User::load(1);
    $this->assertTrue(in_array('auto_generated_admin_9_2_0', $user->getRoles()));
  }

  /**
   * Checks whether user 1 has an admin role.
   *
   * @return bool
   *   Whether user 1 has the administrator role.
   */
  protected function userOneHasAdminRole() {
    $user = User::load(1);

    $rids = $user->getRoles();

    $roles = Role::loadMultiple($rids);

    foreach ($roles as $role) {
      if ($role->isAdmin()) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
