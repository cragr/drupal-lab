<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests setting focus via AJAX command.
 *
 * @group Ajax
 */
class FocusFirstCommandTest extends WebDriverTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests AjaxFocusFirstCommand on a page.
   */
  public function testFocusFirst() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('ajax-test/focus-first');
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertNotContains($has_focus_id,
    ['edit-first-input',
      'edit-first-container-input',
    ]);

    // Confirm that focus does not change if the selector targets a
    // non-focusable container containing no focusable elements.
    $page->pressButton('selectornothingfocusable');
    $this->assertNull($assert_session->waitForElementVisible('css', '[data-has-focus]'));
    $this->assertEquals($has_focus_id, $this->getSession()->evaluateScript('document.activeElement.id'));

    // Confirm that focus does not change if the page has no match for the
    // provided selector.
    $page->pressButton('selectornotexist');
    $this->assertNull($assert_session->waitForElementVisible('css', '[data-has-focus]'));
    $this->assertEquals($has_focus_id, $this->getSession()->evaluateScript('document.activeElement.id'));

    $page->pressButton('focusfirstcontainer');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#edit-first-container-input[data-has-focus]'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-first-container-input', $has_focus_id);

    // Test focusFirst
    $page->pressButton('focusfirstform');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#ajax-test-focus-first-command-form #edit-first-input[data-has-focus]'));

    // Confirm the form has more than one input to confirm that
    // FocusFirstCommand focuses the first element in the container.
    $this->assertNotNull($page->find('css', '#ajax-test-focus-first-command-form #edit-second-input'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-first-input', $has_focus_id);

    // Confirm that the selector provided to FocusFirstCommand will use the
    // first match in the DOM as the container.
    $page->pressButton('selectormultiplematches');
    $this->assertNotNull($assert_session->waitForElementVisible('css', '#edit-inside-same-selector-container-1[data-has-focus]'));
    $this->assertNotNull($page->findById('edit-inside-same-selector-container-2'));
    $this->assertNull($assert_session->waitForElementVisible('css', '#edit-inside-same-selector-container-2[data-has-focus]'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-inside-same-selector-container-1', $has_focus_id);

    // Confirm that if a container has no focusable children, but is itself
    // focusable, then that container recieves focus.
    $page->pressButton('focusablecontainernofocusablechildren');
    $this->assertNotNull( $assert_session->waitForElementVisible('css', '#focusable-container-without-focusable-children[data-has-focus]'));
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('focusable-container-without-focusable-children', $has_focus_id);

  }

}
