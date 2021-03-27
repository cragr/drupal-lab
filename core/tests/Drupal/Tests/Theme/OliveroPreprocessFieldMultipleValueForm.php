<?php

namespace Drupal\Tests\Theme;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the Olivero theme's hook_preprocess_field_multiple_value_form
 *
 * @group olivero
 */
final class OliveroPreprocesFieldMultipleValueForm extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../../../themes/olivero/olivero.theme';
  }

  /**
   * Tests that the disabled variable is made available to the template.
   */
  public function testMakeDisabledAvailable() {
    $variables = [
      'element' => [
        '#disabled' => TRUE,
      ],
    ];
    olivero_preprocess_field_multiple_value_form($variables);
    $this->assertEquals(TRUE, $variables['disabled']);
  }

  /**
   * Tests that no header classes are added if the field is not required.
   */
  public function testDefaultHeaderAttributes() {
    $variables = [
      'multiple' => TRUE,
      'element' => [
        '#required' => FALSE,
        '#title' => "Title"
      ],
    ];
    $header_attributes = ['class' => ['form-item__label', 'form-item__label--multiple-value-form']];

    olivero_preprocess_field_multiple_value_form($variables);
    $this->assertEquals($header_attributes, $variables['table']['#header'][0]['data']['#attributes']);
  }

  /**
   * Tests that header classes are added if the field is required.
   */
  public function testRequiredHeaderAttributes() {
    $variables = [
      'multiple' => TRUE,
      'element' => [
        '#required' => TRUE,
        '#title' => "Title"
      ],
    ];
    $header_attributes = ['class' => ['form-item__label', 'form-item__label--multiple-value-form', 'js-form-required', 'form-required']];

    olivero_preprocess_field_multiple_value_form($variables);
    $this->assertEquals($header_attributes, $variables['table']['#header'][0]['data']['#attributes']);
  }

}
