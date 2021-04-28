<?php

namespace Drupal\content_translation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the entity changed timestamp.
 *
 * @internal
 *
 * @Constraint(
 *   id = "ContentTranslationSynchronizedFields",
 *   label = @Translation("Content translation synchronized fields", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class ContentTranslationSynchronizedFieldsConstraint extends Constraint {

  /**
   * Changing non-translatable field elements on non-default revision message.
   *
   * In this case "elements" refers to "field properties". It is what we are
   * using in the UI elsewhere.
   */
  public $defaultRevisionMessage = 'Non-translatable field elements can only be changed when updating the current revision.';

  /**
   * Changing non-translatable field elements on non-default language message.
   *
   * In this case "elements" refers to "field properties". It is what we are
   * using in the UI elsewhere.
   */
  public $defaultTranslationMessage = 'Non-translatable field elements can only be changed when updating the original language.';

}
