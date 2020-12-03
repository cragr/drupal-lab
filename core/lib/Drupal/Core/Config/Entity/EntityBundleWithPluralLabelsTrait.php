<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Allows bundle configuration entities to support label plural variants.
 */
trait EntityBundleWithPluralLabelsTrait {

  /**
   * The indefinite singular name of the bundle.
   *
   * @var string
   */
  protected $label_singular;

  /**
   * The indefinite plural name of the bundle.
   *
   * @var string
   */
  protected $label_plural;

  /**
   * A list of definite singular/plural count label versions.
   *
   * Unlimited definite singular/plural count labels can be defined in order to
   * cover various contexts where they are used. The array keys are strings,
   * identifying the context. For example, a site might need two or more
   * versions of the count labels:
   * - singular '1 item', plural '@count items',
   * - singular '1 item was found', plural '@count items were found'.
   * For this case the value of this property is:
   * @code
   * [
   *   'default' => "1 item\x03@count items",
   *   'items_found' => "1 item was found\x03@count items were found",
   * ]
   * @endcode
   * Note that the context ('default', 'items_found') is an arbitrary string
   * identifier used to retrieve the desired version. If there's only one
   * context, the context identifier can be omitted:
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
  public function getSingularLabel(): ?string {
    // Provide a fallback in case label_singular is not set yet.
    if (empty($this->label_singular)) {
      if ($label = $this->label()) {
        $this->label_singular = mb_strtolower($label);
      }
    }
    return $this->label_singular;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluralLabel(): ?string {
    // Provide a fallback in case label_plural is not set yet.
    if (empty($this->label_plural)) {
      if ($label = $this->label()) {
        $arguments = ['@label' => mb_strtolower($label)];
        $options = ['langcode' => $this->language()->getId()];
        $this->label_plural = new TranslatableMarkup('@label items', $arguments, $options);
      }
    }
    return $this->label_plural;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountLabel(int $count, ?string $variant = NULL): ?string {
    $label_count_versions = (array) $this->label_count;

    // If the context was not passed, pickup the first version of count label.
    $context = $context ?: key($label_count_versions);

    $index = static::getPluralIndex($count);
    if ($index === -1) {
      // If the index cannot be computed, fallback to a single plural variant.
      $index = $count > 1 ? 1 : 0;
    }

    $label_count = empty($label_count_versions[$context]) ? [] : explode(PoItem::DELIMITER, $label_count_versions[$context]);
    if (!empty($label_count[$index])) {
      return new FormattableMarkup($label_count[$index], ['@count' => $count]);
    }
    if (($singular = $this->getSingularLabel()) && ($plural = $this->getPluralLabel())) {
      $arguments = ['@singular' => $singular, '@plural' => $plural];
      return new PluralTranslatableMarkup($count, '1 @singular', '@count @plural', $arguments);
    }
    return NULL;
  }

  /**
   * Gets the plural index through the gettext formula.
   *
   * @param int $count
   *   Number to return plural for.
   *
   * @return int
   *   The numeric index of the plural variant to use for this $langcode and
   *   $count combination or -1 if the language was not found or does not have a
   *   plural formula.
   *
   * @todo Remove this method when https://www.drupal.org/node/2766857 gets in.
   */
  protected static function getPluralIndex(int $count): int {
    // We have to test both if the function and the service exist since in
    // certain situations it is possible that locale code might be loaded but
    // the service does not exist. For example, where the parent test site has
    // locale installed but the child site does not.
    // @todo Refactor in https://www.drupal.org/node/2660338 so this code does
    //   not depend on knowing that the Locale module exists.
    if (function_exists('locale_get_plural') && \Drupal::hasService('locale.plural.formula')) {
      return locale_get_plural($count);
    }
    return -1;
  }

}
