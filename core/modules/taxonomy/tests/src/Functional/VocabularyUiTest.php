<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the taxonomy vocabulary interface.
 *
 * @group taxonomy
 */
class VocabularyUiTest extends TaxonomyTestBase {

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy']));
    $this->vocabulary = $this->createVocabulary();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Create, edit and delete a vocabulary via the user interface.
   */
  public function testVocabularyInterface() {
    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy');

    // Create a new vocabulary.
    $this->clickLink(t('Add vocabulary'));
    $edit = [];
    $vid = mb_strtolower($this->randomMachineName());
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['vid'] = $vid;
    $this->submitForm($edit, 'Save');
    $this->assertRaw(t('Created new vocabulary %name.', ['%name' => $edit['name']]));

    // Edit the vocabulary.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertText($edit['name']);
    $this->assertText($edit['description']);
    $this->assertSession()->linkByHrefExists(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $edit['vid']])->toString());
    $this->clickLink(t('Edit vocabulary'));
    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertText($edit['name']);
    $this->assertText($edit['description']);

    // Try to submit a vocabulary with a duplicate machine name.
    $edit['vid'] = $vid;
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, 'Save');
    $this->assertText('The machine-readable name is already in use. It must be unique.');

    // Try to submit an invalid machine name.
    $edit['vid'] = '!&^%';
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, 'Save');
    $this->assertText('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Ensure that vocabulary titles are escaped properly.
    $edit = [];
    $edit['name'] = 'Don\'t Panic';
    $edit['description'] = $this->randomMachineName();
    $edit['vid'] = 'don_t_panic';
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, 'Save');

    $site_name = $this->config('system.site')->get('name');
    $this->assertSession()->titleEquals("Don't Panic | $site_name");
  }

  /**
   * Changing weights on the vocabulary overview with two or more vocabularies.
   */
  public function testTaxonomyAdminChangingWeights() {
    // Create some vocabularies.
    for ($i = 0; $i < 10; $i++) {
      $this->createVocabulary();
    }
    // Get all vocabularies and change their weights.
    $vocabularies = Vocabulary::loadMultiple();
    $edit = [];
    foreach ($vocabularies as $key => $vocabulary) {
      $weight = -$vocabulary->get('weight');
      $vocabularies[$key]->set('weight', $weight);
      $edit['vocabularies[' . $key . '][weight]'] = $weight;
    }
    // Saving the new weights via the interface.
    $this->drupalPostForm('admin/structure/taxonomy', $edit, 'Save');

    // Load the vocabularies from the database.
    $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $new_vocabularies = Vocabulary::loadMultiple();

    // Check that the weights are saved in the database correctly.
    foreach ($vocabularies as $key => $vocabulary) {
      $this->assertEquals($new_vocabularies[$key]->get('weight'), $vocabularies[$key]->get('weight'), 'The vocabulary weight was changed.');
    }
  }

  /**
   * Test the vocabulary overview with no vocabularies.
   */
  public function testTaxonomyAdminNoVocabularies() {
    // Delete all vocabularies.
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $key => $vocabulary) {
      $vocabulary->delete();
    }
    // Confirm that no vocabularies are found in the database.
    $this->assertEmpty(Vocabulary::loadMultiple(), 'No vocabularies found.');
    $this->drupalGet('admin/structure/taxonomy');
    // Check the default message for no vocabularies.
    $this->assertText('No vocabularies available.');
  }

  /**
   * Deleting a vocabulary.
   */
  public function testTaxonomyAdminDeletingVocabulary() {
    // Create a vocabulary.
    $vid = mb_strtolower($this->randomMachineName());
    $edit = [
      'name' => $this->randomMachineName(),
      'vid' => $vid,
    ];
    $this->drupalPostForm('admin/structure/taxonomy/add', $edit, 'Save');
    $this->assertText('Created new vocabulary');

    // Check the created vocabulary.
    $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $vocabulary = Vocabulary::load($vid);
    $this->assertNotEmpty($vocabulary, 'Vocabulary found.');

    // Delete the vocabulary.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id());
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the vocabulary %name?', ['%name' => $vocabulary->label()]));
    $this->assertText('Deleting a vocabulary will delete all the terms in it. This action cannot be undone.');

    // Confirm deletion.
    $this->submitForm([], 'Delete');
    $this->assertRaw(t('Deleted vocabulary %name.', ['%name' => $vocabulary->label()]));
    $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $this->assertNull(Vocabulary::load($vid), 'Vocabulary not found.');
  }

}
