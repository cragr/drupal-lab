<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Gettext\PoItem;
use Drupal\Component\Render\FormattableMarkup;

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
  public function getSingularLabel(): ?string {
    return $this->label_singular;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluralLabel(): ?string {
    return $this->label_plural;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountLabel(int $count, ?string $variant = NULL): ?string {
    $label_count_variants = (array) $this->label_count;
    if ($label_count_variants) {
      // If no variant ID was passed, pickup the first version of count label.
      $variant = $variant ?: key($label_count_variants);
      $index = static::getPluralIndex($count);
      if ($index === -1) {
        // If the index cannot be computed, fallback to a single plural variant.
        $index = $count > 1 ? 1 : 0;
      }

      $label_count = !empty($label_count_variants[$variant]) ? explode(PoItem::DELIMITER, $label_count_variants[$variant]) : [];
      if (!empty($label_count[$index])) {
        return new FormattableMarkup($label_count[$index], ['@count' => $count]);
      }
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
   *   The numeric index of the plural variant to use for the current language
   *   and the given $count number or -1 if the language was not found or does
   *   not have a plural formula.
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
