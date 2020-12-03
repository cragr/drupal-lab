<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an entity type bundle info.
 */
interface EntityTypeBundleInfoInterface {

  /**
   * Get the bundle info of all entity types.
   *
   * @return array
   *   An array of bundle information where the outer array is keyed by entity
   *   type. The next level is keyed by the bundle name. The inner arrays are
   *   associative arrays of bundle information, such as the label for the
   *   bundle.
   */
  public function getAllBundleInfo();

  /**
   * Gets the bundle info of an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An array of bundle information where the outer array is keyed by the
   *   bundle name, or the entity type name if the entity does not have bundles.
   *   The inner arrays are associative arrays of bundle information, such as
   *   the label for the bundle.
   */
  public function getBundleInfo($entity_type_id);

  /**
   * Clears static and persistent bundles.
   */
  public function clearCachedBundles();

  /**
   * Gets the count label for a given bundle.
   *
   * @param string $entity_type_id
   *   The bundle's entity type ID.
   * @param string $bundle
   *   The bundle.
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
   *   The count label. NULL if is returned in one of the following cases:
   *   - The bundle didn't define a 'label_count' variant list.
   *   - There's no plural formula for the given $count.
   *
   * @throws \InvalidArgumentException
   *   If the passed entity type, bundle or variant doesn't exist.
   */
  public function getBundleCountLabel(string $entity_type_id, string $bundle, int $count, ?string $variant = NULL): ?string;

}
