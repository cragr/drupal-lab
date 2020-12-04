<?php


namespace Drupal\entity_test\Entity;

use Drupal\Core\Config\Entity\EntityBundleWithPluralLabelsInterface;
use Drupal\Core\Config\Entity\EntityBundleWithPluralLabelsTrait;

/**
 * Defines the Test entity bundle with plural labels configuration entity.
 *
 * @ConfigEntityType(
 *   id = "entity_test_bundle_plural_labels",
 *   bundle_of = "entity_test_with_bundle",
 *   config_prefix = "entity_test_bundle_plural_labels",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "label_singular",
 *     "label_plural",
 *     "label_count",
 *   },
 * )
 */
class EntityTestBundleWithPluralLabels extends EntityTestBundle implements EntityBundleWithPluralLabelsInterface {

  use EntityBundleWithPluralLabelsTrait;

}
