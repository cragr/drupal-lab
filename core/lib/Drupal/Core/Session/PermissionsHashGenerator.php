<?php

namespace Drupal\Core\Session;

use Drupal\Core\PrivateKey;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;

/**
 * Generates and caches the permissions hash for a user.
 */
class PermissionsHashGenerator implements PermissionsHashGeneratorInterface {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The cache backend interface to use for the persistent cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache backend interface to use for the static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $static;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PermissionsHashGenerator object.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend interface to use for the persistent cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface $static
   *   The cache backend interface to use for the static cache.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager used to retrieve a storage handler from.
   */
  public function __construct(PrivateKey $private_key, CacheBackendInterface $cache, CacheBackendInterface $static, EntityTypeManagerInterface $entity_type_manager) {
    $this->privateKey = $private_key;
    $this->cache = $cache;
    $this->static = $static;
    if ($entity_type_manager === NULL) {
      @trigger_error('Calling ' . __METHOD__ . ' without $entity_type_manager argument is deprecated in drupal:9.2.0 and will be required in drupal:10.0.0. See https://www.drupal.org/node/2910500', E_USER_DEPRECATED);
      $entity_type_manager = \Drupal::entityTypeManager();
    }
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * Cached by role, invalidated whenever permissions change.
   */
  public function generate(AccountInterface $account) {
    // Admin roles have all permissions implicitly assigned. Use a different,
    // unique identifier for the hash.
    $storage = $this->entityTypeManager->getStorage('user_role');
    foreach ($storage->loadMultiple($account->getRoles()) as $role) {
      /** @var \Drupal\user\RoleInterface $role */
      if ($role->isAdmin()) {
        return $this->hash('is-admin');
      }
    }

    $sorted_roles = $account->getRoles();
    sort($sorted_roles);
    $role_list = implode(',', $sorted_roles);
    $cid = "user_permissions_hash:$role_list";
    if ($static_cache = $this->static->get($cid)) {
      return $static_cache->data;
    }
    else {
      $tags = Cache::buildTags('config:user.role', $sorted_roles, '.');
      if ($cache = $this->cache->get($cid)) {
        $permissions_hash = $cache->data;
      }
      else {
        $permissions_hash = $this->doGenerate($sorted_roles);
        $this->cache->set($cid, $permissions_hash, Cache::PERMANENT, $tags);
      }
      $this->static->set($cid, $permissions_hash, Cache::PERMANENT, $tags);
    }

    return $permissions_hash;
  }

  /**
   * Generates a hash that uniquely identifies the user's permissions.
   *
   * @param string[] $roles
   *   The user's roles.
   *
   * @return string
   *   The permissions hash.
   */
  protected function doGenerate(array $roles) {
    // @todo Once Drupal gets rid of user_role_permissions(), we should be able
    // to inject the user role controller and call a method on that instead.
    $permissions_by_role = user_role_permissions($roles);
    foreach ($permissions_by_role as $role => $permissions) {
      sort($permissions);
      // Note that for admin roles (\Drupal\user\RoleInterface::isAdmin()), the
      // permissions returned will be empty ($permissions = []). Therefore the
      // presence of the role ID as a key in $permissions_by_role is essential
      // to ensure that the hash correctly recognizes admin roles. (If the hash
      // was based solely on the union of $permissions, the admin roles would
      // effectively be no-ops, allowing for hash collisions.)
      $permissions_by_role[$role] = $permissions;
    }
    return $this->hash(serialize($permissions_by_role));
  }

  /**
   * Hashes the given string.
   *
   * @param string $identifier
   *   The string to be hashed.
   *
   * @return string
   *   The hash.
   */
  protected function hash($identifier) {
    return hash('sha256', $this->privateKey->get() . Settings::getHashSalt() . $identifier);
  }

}
