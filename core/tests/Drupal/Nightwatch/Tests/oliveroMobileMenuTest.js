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
  'Verify mobile menu exists': (browser) => {
    browser
      .drupalRelativeURL('/')
      .assert.not.visible('#header-nav');

    browser.click('button.mobile-nav-button', function () {
      browser.assert.visible('#header-nav');
      browser.assert.visible('#search-block-form');
      for (let i = 0; i < 19; i++) {
        browser.keys(browser.Keys.TAB);
      }

      browser.execute(function () {
        return document.activeElement.matches('#header-nav *, button.mobile-nav-button');
      }, [], (result) => {
        browser.assert.ok(result.value);
      });
      browser.pause();
    });
  },
};
