// Nightwatch suggests non-ES6 functions when using the execute method.
// eslint-disable func-names, prefer-arrow-callback

const mobileNavButtonSelector = 'button.mobile-nav-button';
const headerNavSelector = '#header-nav';
const linkSubMenuId = 'home-submenu-1';
const buttonSubMenuId = 'button-submenu-2';

module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
      installProfile: 'minimal',
    });
    browser.resizeWindow(1000, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify mobile menu and submenu functionality': (browser) => {
    browser.drupalRelativeURL('/').assert.not.visible(headerNavSelector);
    browser.click(mobileNavButtonSelector, function () {
      browser.assert.visible(headerNavSelector);

      // Test interactions for normal <a> menu links.
      browser.assert.not.visible(`#${linkSubMenuId}`);
      browser.assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'false',
      );
      browser.click(`[aria-controls="${linkSubMenuId}"]`, function () {
        browser.assert.visible(`#${linkSubMenuId}`);
        browser.assert.attributeEquals(
          `[aria-controls="${linkSubMenuId}"]`,
          'aria-expanded',
          'true',
        );
      });

      // Test interactions for route:<button> menu links.
      browser.assert.not.visible(`#${buttonSubMenuId}`);
      browser.assert.attributeEquals(
        `[aria-controls="${buttonSubMenuId}"]`,
        'aria-expanded',
        'false',
      );
      browser.click(`[aria-controls="${buttonSubMenuId}"]`, function () {
        browser.assert.visible(`#${buttonSubMenuId}`);
        browser.assert.attributeEquals(
          `[aria-controls="${buttonSubMenuId}"]`,
          'aria-expanded',
          'true',
        );
      });
    });
  },
  'Verify mobile menu focus trap': (browser) => {
    browser.drupalRelativeURL('/').click(mobileNavButtonSelector, function () {
      // Send the tab key 17 times.
      // @todo test shift+tab functionality when
      // https://www.drupal.org/project/drupal/issues/3191077 is committed.
      for (let i = 0; i < 17; i++) {
        browser.keys(browser.Keys.TAB);
        browser.pause(50);
      }

      // Ensure that focus trap keeps focused element within the navigation.
      browser.execute(
        function (mobileNavButtonSelector, headerNavSelector) {
          // Verify focused element is still within the focus trap.
          return document.activeElement.matches(
            `${headerNavSelector} *, ${mobileNavButtonSelector}`,
          );
        },
        [mobileNavButtonSelector, headerNavSelector],
        (result) => {
          browser.assert.ok(result.value);
        },
      );
    });
  },
};
