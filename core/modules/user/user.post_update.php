<?php

/**
 * @file
 * Post update functions for User module.
 */

use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Implements hook_removed_post_updates().
 */
function user_removed_post_updates() {
  return [
    'user_post_update_enforce_order_of_permissions' => '9.0.0',
  ];
}

/**
 * Grant user 1 a role with the setting "is_admin" is true.
 *
 * After removing the special user status of user 1, we want to make sure that
 * the user 1 has a role with the setting "is_admin" is true.
 *
 * @see https://www.drupal.org/node/2910500
 */
function user_post_update_user_1_with_admin_role() {
  $user = User::load(1);

  $admin_roles_rids = \Drupal::entityQuery('user_role')->condition('is_admin', TRUE)->execute();

  if ($admin_roles_rids) {
    $admin_roles = Role::loadMultiple($admin_roles_rids);
    $role_with_id_administrator = NULL;

    foreach ($admin_roles as $admin_role) {
      // Check if the user 1 has any of the roles with the setting "is_admin" is
      // true. when that is the case, we do not need to do anything.
      if ($user->hasRole($admin_role->id())) {
        return;
      }
      if ($admin_role->id() == 'administrator') {
        $role_with_id_administrator = $admin_role;
      }
    }

    // When the user 1 has no role assigned with the setting "is_admin" is true,
    // then give that user one of the existing "is_admin" roles.
    if ($role_with_id_administrator) {
      // When the role with id "administrator" exists and that role has the
      // setting "is_admin" is true, then assign that to to the user 1.
      $user->addRole($role_with_id_administrator->id());
    }
    else {
      $admin_role = reset($admin_roles);
      $user->addRole($admin_role->id());
    }
    $user->save();
  }
  else {
    $administrator_role = Role::load('administrator');

    // When the role with id "administrator" does not exists, then create a role
    // with that id and add the setting "is_admin" is true. Then assign that
    // role to user 1.
    if (!$administrator_role) {
      $role = Role::create([
        'is_admin' => TRUE,
        'id' => 'administrator',
        'label' => 'Administrator',
      ]);
      $role->save();

      $user->addRole($role->id());
      $user->save();
    }
    else {
      // When the role with id "administrator" does exists and does not have the
      // setting "is_admin" is true, then create a new role with the setting
      // "is_admin" and give the fallback name and label.
      module_load_install('user');
      $administrator_role_post_update = Role::create([
        'is_admin' => TRUE,
        'id' => USER_UPDATE_FALLBACK_ADMIN_ROLE_ID,
        'label' => USER_UPDATE_FALLBACK_ADMIN_ROLE_LABEL,
      ]);
      $administrator_role_post_update->save();

      $user->addRole($administrator_role_post_update->id());
      $user->save();
    }
  }

}
