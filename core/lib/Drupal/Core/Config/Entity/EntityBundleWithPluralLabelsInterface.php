<?php

namespace Drupal\Core\Config\Entity;

/**
 * Allows bundle configuration entities to support label plural variants.
 */
interface EntityBundleWithPluralLabelsInterface {

  /**
   * Returns the singular label of the bundle.
   *
   * @return string|null
   *   The singular label or NULL if it was not set.
   */
  public function getSingularLabel(): ?string;

  /**
   * Returns the plural label of the bundle.
   *
   * @return string|null
   *   The plural label or NULL if it was not set.
   */
  public function getPluralLabel(): ?string;

}
