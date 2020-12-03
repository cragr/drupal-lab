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

  /**
   * Gets the count label of the bundle.
   *
   * @param int $count
   *   The item count to display if the plural form was requested.
   * @param string|null $variant
   *   (optional) The optional variant of the count label. A bundle entity can
   *   define unlimited definite singular/plural count labels in order to cover
   *   various contexts where they are used. Pass the variant, as a string
   *   identifier, to get the appropriate version of the count label. Omit the
   *   variant if there's only one version of the definite singular/plural count
   *   label. Defaults to NULL.
   *
   * @return string|null
   *   The count label or NULL if it cannot be computed.
   */
  public function getCountLabel(int $count, ?string $variant = NULL): ?string;

}
