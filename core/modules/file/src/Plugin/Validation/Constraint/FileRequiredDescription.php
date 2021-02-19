<?php

namespace Drupal\file\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constrains the existence of a file description if it has been configured.
 *
 * @Constraint(
 *   id = "FileRequiredDescription",
 *   label = @Translation("File required description", context = "Validation"),
 * )
 */
class FileRequiredDescription extends Constraint {

  /**
   * Constraint violation message template.
   *
   * @var string
   */
  public $message = 'The @name field description is required.';

}
