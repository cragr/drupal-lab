// Nightwatch suggests non-ES6 functions when using the execute method.
// eslint-disable func-names, prefer-arrow-callback

const mobileNavButtonSelector = 'button.mobile-nav-button';
const headerNavSelector = '#header-nav';
const subMenuId = 'home-submenu-1';

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
    browser.drupalRelativeURL('/').assert.not.visible(headerNavSelector);
    browser.click(mobileNavButtonSelector, function () {
      browser.assert.visible(headerNavSelector);
      browser.assert.visible('#search-block-form');

      // Send the tab key 19 times.
      for (let i = 0; i < 19; i++) {
        browser.keys(browser.Keys.TAB);
      }

      // Ensure that focus trap keeps focused element within the navigation.
      browser.execute(
        function (mobileNavButtonSelector, headerNavSelector) {
          return document.activeElement.matches(
            `${headerNavSelector} *, ${mobileNavButtonSelector}`,
          );
        },
        [mobileNavButtonSelector, headerNavSelector],
        (result) => {
          browser.assert.ok(result.value);
        },
      );

      // Ensure that submenu is not visible.
      browser.assert.not.visible(`#${subMenuId}`);
      browser.assert.attributeEquals(
        `[aria-controls="${subMenuId}"]`,
        'aria-expanded',
        'false',
      );
      browser.click('[aria-controls="home-submenu-1"]', function () {
        browser.assert.visible(`#${subMenuId}`);
        browser.assert.attributeEquals(
          `[aria-controls="${subMenuId}"]`,
          'aria-expanded',
          'true',
        );
      });
    });
  },
};
