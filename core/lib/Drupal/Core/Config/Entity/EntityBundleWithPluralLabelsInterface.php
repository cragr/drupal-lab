<?php

namespace Drupal\Core\Config\Entity;

/**
 * Allows bundle configuration entities to support label plural variants.
 */
interface EntityBundleWithPluralLabelsInterface {

  /**
   * Sets the singular label of the bundle.
   *
   * @param string $singular_label
   *   The singular label.
   *
   * @return $this
   */
  public function setSingularLabel(string $singular_label): self;

  /**
   * Sets the plural label of the bundle.
   *
   * @param string $plural_label
   *   The plural label.
   *
   * @return $this
   */
  public function setPluralLabel(string $plural_label): self;

  /**
   * Sets the count label.
   *
   * @param string[] $count_label
   *   The list of count label variants.
   *
   * @return $this
   */
  public function setCountLabel(array $count_label): self;

}
