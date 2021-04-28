<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests content translation for vocabularies.
 *
 * @group taxonomy
 */
class VocabularyTranslationTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_translation', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Languages to enable.
   *
   * @var array
   */
  protected $additionalLangcodes = ['es'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an administrative user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer taxonomy',
      'administer content translation',
    ]));

    // Add languages.
    foreach ($this->additionalLangcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
  }

  /**
   * Tests language settings for vocabularies.
   */
  public function testVocabularyLanguage() {
    $this->drupalGet('admin/structure/taxonomy/add');

    // Check that the field to enable content translation is available.
    $this->assertSession()->fieldExists('edit-default-language-content-translation');

    // Create the vocabulary.
    $vid = mb_strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['langcode'] = 'en';
    $edit['vid'] = $vid;
    $edit['default_language[content_translation]'] = TRUE;
    $this->submitForm($edit, 'Save');

    // Check if content translation is enabled on the edit page.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vid);
    $this->assertSession()->checkboxChecked('edit-default-language-content-translation');
  }

  /**
   * Test vocabulary name translation for overview page and Reset Page.
   */
  public function testVocabularyTitleLabelTranslation() {
    // Getting taxonomy vocabulary add form
    $this->drupalGet('admin/structure/taxonomy/add');

    // Create the vocabulary.
    $vid = mb_strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['langcode'] = 'en';
    $edit['vid'] = $vid;
    $edit['default_language[content_translation]'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $langcode = $this->additionalLangcodes[0];
    $vid_name = $edit['name'];
    $translated_vid_name = "Translated $vid_name";

    // Assert that the name label is displayed on the translation form with the right value.
    $this->drupalGet("admin/structure/taxonomy/manage/$vid/translate/$langcode/add");
    $this->assertText($vid_name);

    // Translate the name label.
    $this->drupalPostForm(NULL, ["translation[config_names][taxonomy.vocabulary.$vid][name]" => $translated_vid_name], t('Save translation'));

    // Assert that the right name label is displayed on the taxonomy term overview page. The
    // translations are created in this test; therefore, the assertions do not
    // use t(). If t() were used then the correct langcodes would need to be
    // provided.
    $this->drupalGet("admin/structure/taxonomy/manage/$vid/overview");
    $this->assertText($vid_name);
    $this->drupalGet("$langcode/admin/structure/taxonomy/manage/$vid/overview");
    $this->assertText($translated_vid_name);

    // Assert that the right name label is displayed on the taxonomy reset page. The
    // translations are created in this test; therefore, the assertions do not
    // use t(). If t() were used then the correct langcodes would need to be
    // provided.
    $this->drupalGet("admin/structure/taxonomy/manage/$vid/reset");
    $this->assertText($vid_name);
    $this->drupalGet("$langcode/admin/structure/taxonomy/manage/$vid/reset");
    $this->assertText($translated_vid_name);
  }

}
