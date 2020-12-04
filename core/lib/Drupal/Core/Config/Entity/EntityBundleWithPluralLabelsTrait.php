<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Gettext\PoItem;

/**
 * Allows bundle configuration entities to support label plural variants.
 */
trait EntityBundleWithPluralLabelsTrait {

  /**
   * The indefinite singular name of the bundle.
   *
   * @var string|null
   */
  protected $label_singular;

  /**
   * The indefinite plural name of the bundle.
   *
   * @var string|null
   */
  protected $label_plural;

  /**
   * A list of definite singular/plural count label variants.
   *
   * Unlimited definite singular/plural count labels can be defined in order to
   * cover various contexts where they are used. The array keys are strings,
   * identifying the context. For example, a site might need two or more
   * versions of the count labels:
   * - singular '1 article', plural '@count article',
   * - singular '1 article was found', plural '@count articles were found'.
   * For this case the value of this property is:
   * @code
   * [
   *   'default' => "1 article\x03@count article",
   *   'items_found' => "1 article was found\x03@count articles were found",
   * ]
   * @endcode
   * Note that the context ('default', 'items_found') is an arbitrary string
   * identifier used to retrieve the desired version. If there's only one
   * variant, the identifier can be omitted:
   * @code
   * [
   *   "1 item\x03@count items",
   * ]
   * @endcode
   * Each value is a definite singular/plural count label with the plural
   * variants separated by ETX (PoItem::DELIMITER).
   *
   * @var string[]|null
   *
   * @see \Drupal\Component\Gettext\PoItem::DELIMITER
   */
  protected $label_count;

  /**
   * {@inheritdoc}
   */
  public function setSingularLabel(string $singular_label): EntityBundleWithPluralLabelsInterface {
    $this->label_singular = $singular_label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluralLabel(string $plural_label): EntityBundleWithPluralLabelsInterface {
    $this->label_plural = $plural_label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCountLabel(array $count_label): EntityBundleWithPluralLabelsInterface {
    $this->label_count = $count_label;
    return $this;
  }

}
