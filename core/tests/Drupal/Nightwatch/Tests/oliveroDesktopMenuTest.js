// Nightwatch suggests non-ES6 functions when using the execute method.
// eslint-disable func-names, prefer-arrow-callback

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
    browser.resizeWindow(1600, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Verify desktop menu click functionality': (browser) => {
    browser
      .drupalRelativeURL('/node')
      .assert.visible(headerNavSelector)
      .assert.not.visible(`#${linkSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${linkSubMenuId}"]`,
        'aria-expanded',
        'false',
      )
      .click(`[aria-controls="${linkSubMenuId}"]`, function () {
        browser.assert.visible(`#${linkSubMenuId}`);
        browser.assert.attributeEquals(
          `[aria-controls="${linkSubMenuId}"]`,
          'aria-expanded',
          'true',
        );
      })

      // Test interactions for route:<button> menu links.
      .assert.not.visible(`#${buttonSubMenuId}`)
      .assert.attributeEquals(
        `[aria-controls="${buttonSubMenuId}"]`,
        'aria-expanded',
        'false',
      )
      .click(`[aria-controls="${buttonSubMenuId}"]`, function () {
        browser.assert.visible(`#${buttonSubMenuId}`);
        browser.assert.attributeEquals(
          `[aria-controls="${buttonSubMenuId}"]`,
          'aria-expanded',
          'true',
        );
      });
  },
  'Verify desktop menu hover functionality': (browser) => {
    browser
      .drupalRelativeURL('/node')
      .waitForElementVisible('body', 1000, function () {
        browser.assert
          .visible(headerNavSelector)
          .moveToElement('link text', 'home', function () {
            browser.assert.visible(`#${linkSubMenuId}`);
            browser.assert.attributeEquals(
              `[aria-controls="${linkSubMenuId}"]`,
              'aria-expanded',
              'true',
            );
          })
          .moveToElement('link text', 'button', function () {
            console.log('button');
            browser.assert.visible(`#${buttonSubMenuId}`);
            browser.assert.attributeEquals(
              `[aria-controls="${buttonSubMenuId}"]`,
              'aria-expanded',
              'true',
            );
          });
      });
  },
};
