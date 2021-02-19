<?php

namespace Drupal\user;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the user role entity type.
 *
 * @see \Drupal\user\Entity\Role
 */
class RoleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'delete':
        $internal_roles = [
          RoleInterface::ANONYMOUS_ID,
          RoleInterface::AUTHENTICATED_ID,
          RoleInterface::ADMINISTRATOR_ID,
        ];
        if (in_array($entity->id(), $internal_roles)) {
          return AccessResult::forbidden();
        }

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
