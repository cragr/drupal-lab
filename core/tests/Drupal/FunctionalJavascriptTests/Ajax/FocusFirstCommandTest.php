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

    $page->pressButton('focusfirstcontainer');
    $assert_session->assertWaitOnAjaxRequest();
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-first-container-input', $has_focus_id);

    $page->pressButton('focusfirstform');
    $assert_session->assertWaitOnAjaxRequest();
    $has_focus_id = $this->getSession()->evaluateScript('document.activeElement.id');
    $this->assertEquals('edit-first-input', $has_focus_id);
  }

}
