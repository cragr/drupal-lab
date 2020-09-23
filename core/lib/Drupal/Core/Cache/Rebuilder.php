<?php

namespace Drupal\Core\Cache;

/**
 * Helper methods for cache rebuild.
 *
 * @ingroup cache
 */
class Rebuilder {

  /**
   * Flushes all caches.
   *
   * Flushes all persistent caches, resets all variables, rebuilds all data
   * structures.
   * At times, it is necessary to re-initialize the entire system to account for
   * changed or new code. This method makes all registered cache bins cleared.
   *
   * This means the entire system is reset so all caches and static variables
   * are effectively empty. After that is guaranteed, information about the
   * currently active code is updated, and rebuild operations are successively
   * called in order to synchronize the active system according to the current
   * information defined in code.
   *
   * All modules need to ensure that all of their caches are flushed when
   * hook_cache_flush() is invoked; any previously known information must no
   * longer exist. All following hook_rebuild() operations must be based on
   * fresh and current system data. All modules must be able to rely on this
   * contract.
   *
   * This function also resets the theme, which means it is not initialized
   * anymore and all previously added JavaScript and CSS is gone. Normally, this
   * function is called as an end-of-POST-request operation that is followed by
   * a redirect, so this effect is not visible. Since the full reset is the
   * whole point of this function, callers need to take care for backing up all
   * needed variables and properly restoring or re-initializing them on their
   * own. For convenience, this function automatically re-initializes the
   * maintenance theme if it was initialized before.
   *
   * @todo Try to clear page/JS/CSS caches last, so cached pages can still be
   *   served during this possibly long-running operation. (Conflict on
   *   bootstrap cache though.)
   *   See: https://www.drupal.org/project/drupal/issues/3160185
   * @todo Add a global lock to ensure that caches are not primed in concurrent
   *   requests. See: https://www.drupal.org/project/drupal/issues/3160185
   *
   * @see \Drupal\Core\Cache\CacheHelper::getBins()
   * @see hook_cache_flush()
   * @see hook_rebuild()
   */
  public static function rebuildAll() {
    // Flush all persistent caches.
    // This is executed based on old/previously known information, which is
    // sufficient, since new extensions cannot have any primed caches yet.
    \Drupal::moduleHandler()->invokeAll('cache_flush');
    self::binsDeleteAll();

    // Flush asset file caches.
    \Drupal::service('asset.css.collection_optimizer')->deleteAll();
    \Drupal::service('asset.js.collection_optimizer')->deleteAll();
    \Drupal::service('cache.query_string')->reset();

    // Reset all static caches.
    drupal_static_reset();

    // Invalidate the container.
    \Drupal::service('kernel')->invalidateContainer();

    // Wipe the Twig PHP Storage cache.
    \Drupal::service('twig')->invalidate();

    // Rebuild module and theme data.
    $module_data = \Drupal::service('extension.list.module')->reset()->getList();
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');
    $theme_handler->refreshInfo();
    // In case the active theme gets requested later in the same request we need
    // to reset the theme manager.
    \Drupal::theme()->resetActiveTheme();

    // Rebuild and reboot a new kernel. A simple DrupalKernel reboot is not
    // sufficient, since the list of enabled modules might have been adjusted
    // above due to changed code.
    $files = [];
    $modules = [];
    foreach ($module_data as $name => $extension) {
      if ($extension->status) {
        $files[$name] = $extension;
        $modules[$name] = $extension->weight;
      }
    }
    $modules = module_config_sort($modules);
    \Drupal::service('kernel')->updateModules($modules, $files);
    // New container, new module handler.
    $module_handler = \Drupal::moduleHandler();

    // Ensure that all modules that are currently supposed to be enabled are
    // actually loaded.
    $module_handler->loadAll();

    // Rebuild all information based on new module data.
    $module_handler->invokeAll('rebuild');

    // Clear all plugin caches.
    \Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

    // Rebuild the menu router based on all rebuilt data.
    // Important: This rebuild must happen last, so the menu router is
    // guaranteed  to be based on up to date information.
    \Drupal::service('router.builder')->rebuild();

    // Re-initialize the maintenance theme, if the current request attempted to
    // use it. Unlike regular usages of this function, the installer and update
    // scripts need to flush all caches during GET requests/page building.
    if (function_exists('_drupal_maintenance_theme')) {
      \Drupal::theme()->resetActiveTheme();
      drupal_maintenance_theme();
    }
  }

  /**
   * Collects all bins and deletes all cache items in the each bin.
   */
  public static function binsDeleteAll() {
    foreach (Cache::getBins() as $bin) {
      $bin->deleteAll();
    }
  }

}
