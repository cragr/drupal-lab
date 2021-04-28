<?php

namespace Drupal\Core\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the IsSuperUserCacheContext service, for "super user or not" caching.
 *
 * Cache context ID: 'user.is_super_user'.
 *
 * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See
 * https://www.drupal.org/node/2910500 for more information.
 *
 * @see https://www.drupal.org/node/2910500
 */
class IsSuperUserCacheContext extends UserCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountInterface $user) {
    @trigger_error('\Drupal\Core\Cache\Context\IsSuperUserCacheContext is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/2910500 for more information.', E_USER_DEPRECATED);
    parent::__construct($user);
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Is super user');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    // Always return 0 as there is no longer a super user. See:
    // https://www.drupal.org/node/540008
    return '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
