<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\form_test\Form\FormTestLabelForm;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests form element labels, required markers and associated output.
 *
 * @group Form
 */
class ElementsLabelsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test form elements, labels, title attributes and required marks output
   * correctly and have the correct label option class if needed.
   */
  public function testFormLabels() {
    $this->drupalGet('form_test/form-labels');

    // Check that the checkbox/radio processing is not interfering with
    // basic placement.
    $elements = $this->xpath('//input[@id="edit-form-checkboxes-test-third-checkbox"]/following-sibling::label[@for="edit-form-checkboxes-test-third-checkbox" and @class="option"]');
    // Verify that label follows field and label option class correct for
    // regular checkboxes.
    $this->assertArrayHasKey(0, $elements);

    // Make sure the label is rendered for checkboxes.
    $elements = $this->xpath('//input[@id="edit-form-checkboxes-test-0"]/following-sibling::label[@for="edit-form-checkboxes-test-0" and @class="option"]');
    // Verify that label 0 found checkbox.
    $this->assertArrayHasKey(0, $elements);

    $elements = $this->xpath('//input[@id="edit-form-radios-test-second-radio"]/following-sibling::label[@for="edit-form-radios-test-second-radio" and @class="option"]');
    // Verify that label follows field and label option class correct for
    // regular radios.
    $this->assertArrayHasKey(0, $elements);

    // Make sure the label is rendered for radios.
    $elements = $this->xpath('//input[@id="edit-form-radios-test-0"]/following-sibling::label[@for="edit-form-radios-test-0" and @class="option"]');
    // Verify that label 0 found radios.
    $this->assertArrayHasKey(0, $elements);

    // Exercise various defaults for checkboxes and modifications to ensure
    // appropriate override and correct behavior.
    $elements = $this->xpath('//input[@id="edit-form-checkbox-test"]/following-sibling::label[@for="edit-form-checkbox-test" and @class="option"]');
    // Verify that label follows field and label option class correct for a
    // checkbox by default.
    $this->assertArrayHasKey(0, $elements);

    // Exercise various defaults for textboxes and modifications to ensure
    // appropriate override and correct behavior.
    $elements = $this->xpath('//label[@for="edit-form-textfield-test-title-and-required" and @class="js-form-required form-required"]/following-sibling::input[@id="edit-form-textfield-test-title-and-required"]');
    // Verify that label precedes textfield, with required marker inside label.
    $this->assertArrayHasKey(0, $elements);

    $elements = $this->xpath('//input[@id="edit-form-textfield-test-no-title-required"]/preceding-sibling::label[@for="edit-form-textfield-test-no-title-required" and @class="js-form-required form-required"]');
    // Verify that label tag with required marker precedes required textfield
    // with no title.
    $this->assertArrayHasKey(0, $elements);

    $elements = $this->xpath('//input[@id="edit-form-textfield-test-title-invisible"]/preceding-sibling::label[@for="edit-form-textfield-test-title-invisible" and @class="visually-hidden"]');
    // Verify that label preceding field and label class is visually-hidden.
    $this->assertArrayHasKey(0, $elements);

    $elements = $this->xpath('//input[@id="edit-form-textfield-test-title"]/preceding-sibling::span[@class="js-form-required form-required"]');
    // Verify that no required marker on non-required field.
    $this->assertArrayNotHasKey(0, $elements);

    $elements = $this->xpath('//input[@id="edit-form-textfield-test-title-after"]/following-sibling::label[@for="edit-form-textfield-test-title-after" and @class="option"]');
    // Verify that label after field and label option class correct for text
    // field.
    $this->assertArrayHasKey(0, $elements);

    $elements = $this->xpath('//label[@for="edit-form-textfield-test-title-no-show"]');
    // Verify that no label tag when title set not to display.
    $this->assertArrayNotHasKey(0, $elements);

    $elements = $this->xpath('//div[contains(@class, "js-form-item-form-textfield-test-title-invisible") and contains(@class, "form-no-label")]');
    // Verify that field class is form-no-label when there is no label.
    $this->assertArrayHasKey(0, $elements);

    // Check #field_prefix and #field_suffix placement.
    $elements = $this->xpath('//span[@class="field-prefix"]/following-sibling::div[@id="edit-form-radios-test"]');
    // Verify that properly placed the #field_prefix element after the label and
    // before the field.
    $this->assertArrayHasKey(0, $elements);

    $elements = $this->xpath('//span[@class="field-suffix"]/preceding-sibling::div[@id="edit-form-radios-test"]');
    // Verify that properly places the #field_suffix element immediately after
    // the form field.
    $this->assertArrayHasKey(0, $elements);

    // Check #prefix and #suffix placement.
    $elements = $this->xpath('//div[@id="form-test-textfield-title-prefix"]/following-sibling::div[contains(@class, \'js-form-item-form-textfield-test-title\')]');
    // Verify that properly places the #prefix element before the form item.
    $this->assertArrayHasKey(0, $elements);

    $elements = $this->xpath('//div[@id="form-test-textfield-title-suffix"]/preceding-sibling::div[contains(@class, \'js-form-item-form-textfield-test-title\')]');
    // Verify that properly places the #suffix element before the form item.
    $this->assertArrayHasKey(0, $elements);

    // Check title attribute for radios and checkboxes.
    $this->assertSession()->elementAttributeContains('css', '#edit-form-checkboxes-title-attribute', 'title', 'Checkboxes test (Required)');
    $this->assertSession()->elementAttributeContains('css', '#edit-form-radios-title-attribute', 'title', 'Radios test (Required)');

    $elements = $this->xpath('//fieldset[@id="edit-form-checkboxes-title-invisible--wrapper"]/legend/span[contains(@class, "visually-hidden")]');
    $this->assertTrue(!empty($elements), "Title/Label not displayed when 'visually-hidden' attribute is set in checkboxes.");

    $elements = $this->xpath('//fieldset[@id="edit-form-radios-title-invisible--wrapper"]/legend/span[contains(@class, "visually-hidden")]');
    $this->assertTrue(!empty($elements), "Title/Label not displayed when 'visually-hidden' attribute is set in radios.");
  }

  /**
   * Tests XSS-protection of element labels.
   */
  public function testTitleEscaping() {
    $this->drupalGet('form_test/form-labels');
    foreach (FormTestLabelForm::$typesWithTitle as $type) {
      $this->assertSession()->responseContains("$type alert('XSS') is XSS filtered!");
      $this->assertSession()->responseNotContains("$type <script>alert('XSS')</script> is XSS filtered!");
    }
  }

  /**
   * Tests different display options for form element descriptions.
   */
  public function testFormDescriptions() {
    $this->drupalGet('form_test/form-descriptions');

    // Check #description placement with #description_display='after'.
    $field_id = 'edit-form-textfield-test-description-after';
    $description_id = $field_id . '--description';
    $elements = $this->xpath('//input[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]/following-sibling::div[@id="' . $description_id . '"]');
    // Verify that properly places the #description element after the form item.
    $this->assertArrayHasKey(0, $elements);

    // Check #description placement with #description_display='before'.
    $field_id = 'edit-form-textfield-test-description-before';
    $description_id = $field_id . '--description';
    $elements = $this->xpath('//input[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]/preceding-sibling::div[@id="' . $description_id . '"]');
    // Verify that properly places the #description element before the form
    // item.
    $this->assertArrayHasKey(0, $elements);

    // Check if the class is 'visually-hidden' on the form element description
    // for the option with #description_display='invisible' and also check that
    // the description is placed after the form element.
    $field_id = 'edit-form-textfield-test-description-invisible';
    $description_id = $field_id . '--description';
    $elements = $this->xpath('//input[@id="' . $field_id . '" and @aria-describedby="' . $description_id . '"]/following-sibling::div[contains(@class, "visually-hidden")]');
    // Verify that properly renders the #description element visually-hidden.
    $this->assertArrayHasKey(0, $elements);
  }

  /**
   * Test forms in theme-less environments.
   */
  public function testFormsInThemeLessEnvironments() {
    $form = $this->getFormWithLimitedProperties();
    $render_service = $this->container->get('renderer');
    // This should not throw any notices.
    $render_service->renderPlain($form);
  }

  /**
   * Return a form with element with not all properties defined.
   */
  protected function getFormWithLimitedProperties() {
    $form = [];

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => 'Fieldset',
    ];

    return $form;
  }

}
