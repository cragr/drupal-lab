<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Installs Drupal in German with a non-default translation filename pattern.
 *
 * @group Installer
 */
class InstallerTranslationFilenameTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Overrides the language code in which to install Drupal.
   *
   * @var string
   */
  protected $langcode = 'de';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    // Configure a custom translation filename pattern without the version.
    $this->settings['config']['locale.settings']['translation']['default_filename'] = (object) [
      'value' => '%project.%language.po',
      'required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // Place a custom local translation in the translations directory.
    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal.de.po', $this->getPo('de'));

    parent::setUpLanguage();

    // After selecting a different language than English, all following screens
    // should be translated already.
    $elements = $this->xpath('//input[@type="submit"]/@value');
    $this->assertSame(current($elements)->getText(), 'Save and continue de');
    $this->translations['Save and continue'] = 'Save and continue de';

    // Check the language direction.
    $direction = current($this->xpath('/@dir'))->getText();
    $this->assertSame($direction, 'ltr');
  }

  /**
   * Verifies the expected behaviors of the installation result.
   */
  public function testInstaller() {
    $assert_session = $this->assertSession();
    $assert_session->addressEquals('user/1');
    $assert_session->statusCodeEquals(200);

    // Verify German was configured but not English.
    $this->drupalGet('admin/config/regional/language');
    $assert_session->pageTextContains('German');
    $assert_session->pageTextNotContains('English');

    // Verify the strings from the translation files were imported.
    $this->drupalGet('admin/config/regional/translate');
    $test_samples = ['Save and continue', 'Anonymous'];
    foreach ($test_samples as $sample) {
      $edit = [];
      $edit['langcode'] = 'de';
      $edit['translation'] = 'translated';
      $edit['string'] = $sample;
      $this->submitForm($edit, t('Filter'));
      $assert_session->pageTextContains($sample . ' de');
    }
  }

  /**
   * Returns the string for the test .po file.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Contents for the test .po file.
   */
  protected function getPo($langcode) {
    return <<<ENDPO
msgid ""
msgstr ""

msgid "Save and continue"
msgstr "Save and continue $langcode"

msgid "Anonymous"
msgstr "Anonymous $langcode"
ENDPO;
  }

}
