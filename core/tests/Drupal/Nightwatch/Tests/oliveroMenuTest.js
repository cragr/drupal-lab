module.exports = {
  '@tags': ['core', 'olivero'],
  before(browser) {
    browser.drupalInstall({
      setupFile:
        'core/tests/Drupal/TestSite/TestSiteOliveroInstallTestScript.php',
      installProfile: 'standard',
    });
    browser.resizeWindow(1400, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'On scroll, menu collapses to burger 🍔 menu': (browser) => {
    browser
      .drupalRelativeURL('/')
      .assert.containsText(
        '#block-olivero-content h2',
        'Congratulations and welcome to the Drupal community!',
      );

    browser.assert.not.visible('button.wide-nav-expand');
    browser.getLocationInView('footer.site-footer', function () {
      this.assert.visible('button.wide-nav-expand');
      this.assert.not.visible('#site-header__inner');
    });
  },
};
