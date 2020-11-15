module.exports = {
  '@tags': ['core', 'ajax'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'JS Ajax test')
        .waitForElementVisible(
          'input[name="modules[js_ajax_test][enable]"]',
          1000,
        )
        .click('input[name="modules[js_ajax_test][enable]"]')
        .click('input[type="submit"]'); // Submit module form.
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test Execution Order': (browser) => {
    browser
      .drupalRelativeURL('/js_ajax_test')
      .waitForElementVisible('body', 1000)
      .click('[data-drupal-selector="edit-test-execution-order-button"]')
      .waitForElementVisible('body', 1000)
      .assert.containsText(
        '#js_ajax_test_form_wrapper',
        '12345',
        'Execution order confirmed',
      );
  },
};
