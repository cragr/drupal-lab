// Nightwatch suggests non-ES6 functions when using the execute method.
// eslint-disable func-names, prefer-arrow-callback

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
      installProfile: 'standard',
    });
    browser.resizeWindow(1000, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify mobile menu functionality': (browser) => {
    browser.drupalRelativeURL('/').assert.not.visible('#header-nav');
    browser.click('button.mobile-nav-button', function () {
      browser.assert.visible('#header-nav');
      browser.assert.visible('#search-block-form');

      // Send the tab key 19 times.
      for (let i = 0; i < 19; i++) {
        browser.keys(browser.Keys.TAB);
      }

      // Ensure that focus trap keeps focused element within the navigation.
      browser.execute(
        function () {
          return document.activeElement.matches(
            '#header-nav *, button.mobile-nav-button',
          );
        },
        [],
        (result) => {
          browser.assert.ok(result.value);
        },
      );

      // Ensure that submenu is not visible.
      browser.assert.not.visible('#home-submenu-1');
      browser.assert.attributeEquals(
        '[aria-controls="home-submenu-1"]',
        'aria-expanded',
        'false'
      )
      browser.click('[aria-controls="home-submenu-1"]', function () {
        browser.assert.visible('#home-submenu-1');
        browser.assert.attributeEquals(
          '[aria-controls="home-submenu-1"]',
          'aria-expanded',
          'true'
        )
      });
    });
  },
};
