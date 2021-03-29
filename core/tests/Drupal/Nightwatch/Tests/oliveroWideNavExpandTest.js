module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
      installProfile: 'minimal',
    });
    browser.resizeWindow(1400, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'On scroll, menu collapses to burger 🍔 menu': (browser) => {
    browser
      .drupalRelativeURL('/node')
      .assert.containsText(
        '#block-olivero-content h2',
        'Congratulations and welcome to the Drupal community!',
      );

    browser.assert.not.visible('button.wide-nav-expand');
    browser.getLocationInView('footer.site-footer', () => {
      browser.assert.visible('button.wide-nav-expand');
      browser.assert.not.visible('#site-header__inner');
    });

    browser.assert.not.visible('#block-olivero-main-menu');
    browser.click('button.wide-nav-expand', () => {
      browser.assert.visible('#block-olivero-main-menu');
    });
  },
};
