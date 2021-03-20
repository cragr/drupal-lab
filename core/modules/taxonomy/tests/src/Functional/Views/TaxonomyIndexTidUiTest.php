<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\views_ui\Functional\UITestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Entity\View;

/**
 * Tests the taxonomy index filter handler UI.
 *
 * @group taxonomy
 * @see \Drupal\taxonomy\Plugin\views\field\TaxonomyIndexTid
 */
class TaxonomyIndexTidUiTest extends UITestBase {

  use EntityReferenceTestTrait;

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = [
    'test_filter_taxonomy_index_tid',
    'test_taxonomy_term_name',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'views',
    'views_ui',
    'taxonomy_test_views',
  ];

  /**
   * A nested array of \Drupal\taxonomy\TermInterface objects.
   *
   * @var \Drupal\taxonomy\TermInterface[][]
   */
  protected $terms = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->adminUser = $this->drupalCreateUser([
      'administer taxonomy',
      'administer views',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->terms = $this->createVocabularyAndTerms('tags');
    ViewTestData::createTestViews(get_class($this), ['taxonomy_test_views']);

    Vocabulary::create([
      'vid' => 'empty_vocabulary',
      'name' => 'Empty Vocabulary',
    ])->save();
  }

  /**
   * Creates a vocabulary and terms for it.
   *
   * @param string $vocab_id
   *   The vocabulary ID.
   *
   * @return \Drupal\taxonomy\TermInterface[][]
   *   The terms.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createVocabularyAndTerms($vocab_id) {

    $vocab = Vocabulary::load($vocab_id);
    if (!$vocab) {
      Vocabulary::create([
        'vid' => $vocab_id,
        'name' => 'Test Vocabulary ' . $vocab_id,
      ])->save();
    }
    // Setup a hierarchy which looks like this:
    // term 0.0
    // term 1.0
    // - term 1.1
    // term 2.0
    // - term 2.1
    // - term 2.2
    $terms = [];
    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j <= $i; $j++) {
        $term = Term::create([
          'vid' => $vocab_id,
          'name' => "Term $i.$j",
          'parent' => isset($this->terms[$i][0]) ? $this->terms[$i][0]->id() : 0,
        ]);
        $term->save();
        $terms[$i][$j] = $term;
      }
    }
    return $terms;
  }

  /**
   * Tests the filter UI.
   */
  public function testFilterUI() {
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');

    $this->assertFieldByXpath('//select[@id="edit-options-value"]', NULL);

    $result = $this->assertSession()->selectExists('edit-options-value')->findAll('css', 'option');

    // Ensure that the expected hierarchy is available in the UI.
    $counter = 0;
    for ($i = 0; $i < 3; $i++) {
      for ($j = 0; $j <= $i; $j++) {
        $option = $result[$counter++];
        $prefix = $this->terms[$i][$j]->parent->target_id ? '-' : '';
        $tid = $option->getAttribute('value');

        $this->assertEqual($prefix . $this->terms[$i][$j]->getName(), $option->getText());
        $this->assertEqual($this->terms[$i][$j]->id(), $tid);
      }
    }

    // Ensure the autocomplete input element appears when using the 'textfield'
    // type.
    $view = View::load('test_filter_taxonomy_index_tid');
    $display =& $view->getDisplay('default');
    $display['display_options']['filters']['tid']['type'] = 'textfield';
    $view->save();
    $this->drupalGet('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid');
    $this->assertSession()->fieldExists('edit-options-value');

    // Tests \Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid::calculateDependencies().
    $expected = [
      'config' => [
        'taxonomy.vocabulary.tags',
      ],
      'content' => [
        'taxonomy_term:tags:' . Term::load(2)->uuid(),
      ],
      'module' => [
        'node',
        'taxonomy',
        'user',
      ],
    ];
    $this->assertSame($expected, $view->calculateDependencies()->getDependencies());
  }

  /**
   * Test filter UI with multiple vocabularies
   */
  public function testFilterUIWithMultipleVocabularies() {
    $terms2 = $this->createVocabularyAndTerms('tags2');
    $node_type = $this->drupalCreateContentType(['type' => 'page']);
    // Create the tag field itself.
    $field_name = 'taxonomy_tags';
    $this->createEntityReferenceField('node', $node_type->id(), $field_name, NULL, 'taxonomy_term');
    $node0 = $this->drupalCreateNode([
      'type' => 'page',
      'taxonomy_tags' => $this->terms[0][0]->id(),
    ]);
    $node0->save();
    $node1 = $this->drupalCreateNode([
      'type' => 'page',
      'taxonomy_tags' => $terms2[0][0]->id(),
    ]);
    $node1->save();
    $node2 = $this->drupalCreateNode([
      'type' => 'page',
      'taxonomy_tags' => [$this->terms[0][0]->id(), $terms2[0][0]->id()],
    ]);
    $node2->save();
    $edit = [
      'options[vids][tags]' => TRUE,
      'options[vids][tags2]' => TRUE,
      'options[type]' => 'textfield',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler-extra/test_filter_taxonomy_index_tid/default/filter/tid', $edit, 'Apply');
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid', [], 'Expose filter');
    $edit = [
      'options[operator]' => 'and',
      'options[value]' => '',
      'options[reduce_duplicates]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Apply');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->drupalGet('test-filter-taxonomy-index-tid', ['query' => ['tid' => '']]);
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertIdentical(3, count($xpath));
    $this->drupalGet('test-filter-taxonomy-index-tid', ['query' => ['tid' => "t1 ({$this->terms[0][0]->id()})"]]);
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertIdentical(2, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [
      ':href' => $node0->toUrl()->toString(),
    ]);
    $this->assertIdentical(1, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [
      ':href' => $node2->toUrl()->toString(),
    ]);
    $this->assertIdentical(1, count($xpath));
    $this->drupalGet('test-filter-taxonomy-index-tid', ['query' => ['tid' => "t2 ({$terms2[0][0]->id()})"]]);
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertIdentical(2, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [
      ':href' => $node1->toUrl()->toString(),
    ]);
    $this->assertIdentical(1, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [
      ':href' => $node2->toUrl()->toString(),
    ]);
    $this->assertIdentical(1, count($xpath));
    $this->drupalGet('test-filter-taxonomy-index-tid', ['query' => ['tid' => "t1 ({$this->terms[0][0]->id()}), t2 ({$terms2[0][0]->id()})"]]);
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertIdentical(1, count($xpath));
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [
      ':href' => $node2->toUrl()->toString(),
    ]);
    $this->assertIdentical(1, count($xpath));
  }

  /**
   * Tests exposed taxonomy filters.
   */
  public function testExposedFilter() {
    $node_type = $this->drupalCreateContentType(['type' => 'page']);

    // Create the tag field itself.
    $field_name = 'taxonomy_tags';
    $this->createEntityReferenceField('node', $node_type->id(), $field_name, NULL, 'taxonomy_term');

    // Create 4 nodes: 1 without a term, 2 with the same term, and 1 with a
    // different term.
    $node1 = $this->drupalCreateNode();
    $node2 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[1][0]->id()]],
    ]);
    $node3 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[1][0]->id()]],
    ]);
    $node4 = $this->drupalCreateNode([
      $field_name => [['target_id' => $this->terms[2][0]->id()]],
    ]);

    // Only the nodes with the selected term should be shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertCount(2, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node2->toUrl()->toString()]);
    $this->assertCount(1, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node3->toUrl()->toString()]);
    $this->assertCount(1, $xpath);

    // Expose the filter.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid', [], 'Expose filter');
    // Set the operator to 'empty' and remove the default term ID.
    $this->submitForm([
      'options[operator]' => 'empty',
      'options[value][]' => [],
    ], 'Apply');
    // Save the view.
    $this->submitForm([], 'Save');

    // After switching to 'empty' operator, the node without a term should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertCount(1, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node1->toUrl()->toString()]);
    $this->assertCount(1, $xpath);

    // Set the operator to 'not empty'.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid', ['options[operator]' => 'not empty'], 'Apply');
    // Save the view.
    $this->submitForm([], 'Save');

    // After switching to 'not empty' operator, all nodes with terms should be
    // shown.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $xpath = $this->xpath('//div[@class="view-content"]//a');
    $this->assertCount(3, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node2->toUrl()->toString()]);
    $this->assertCount(1, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node3->toUrl()->toString()]);
    $this->assertCount(1, $xpath);
    $xpath = $this->xpath('//div[@class="view-content"]//a[@href=:href]', [':href' => $node4->toUrl()->toString()]);
    $this->assertCount(1, $xpath);

    // Select 'Term ID' as the field to be displayed.
    $edit = ['name[taxonomy_term_field_data.tid]' => TRUE];
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_taxonomy_term_name/default/field', $edit, 'Add and configure fields');
    // Select 'Term' and 'Vocabulary' as filters.
    $edit = [
      'name[taxonomy_term_field_data.tid]' => TRUE,
      'name[taxonomy_term_field_data.vid]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/add-handler/test_taxonomy_term_name/default/filter', $edit, 'Add and configure filter criteria');
    // Select 'Empty Vocabulary' and 'Autocomplete' from the list of options.
    $edit = [
      'options[vids][empty_vocabulary]' => TRUE,
      'options[type]' => 'textfield',
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler-extra/test_taxonomy_term_name/default/filter/tid', $edit, 'Apply and continue');
    // Expose the filter.
    $edit = ['options[expose_button][checkbox][checkbox]' => TRUE];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/tid', $edit, 'Expose filter');
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/tid', $edit, 'Apply');
    // Filter 'Taxonomy terms' belonging to 'Empty Vocabulary'.
    $edit = ['options[value][empty_vocabulary]' => TRUE];
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_taxonomy_term_name/default/filter/vid', $edit, 'Apply');
    $this->drupalPostForm('admin/structure/views/view/test_taxonomy_term_name/edit/default', [], 'Save');
    $this->submitForm([], 'Update preview');
    $preview = $this->xpath("//div[@class='view-content']");
    $this->assertTrue(empty($preview), 'No results.');
  }

  /**
   * Tests that an exposed taxonomy filter doesn't show unpublished terms.
   */
  public function testExposedUnpublishedFilterOptions() {
    $this->terms[1][0]->setUnpublished()->save();
    // Expose the filter.
    $this->drupalPostForm('admin/structure/views/nojs/handler/test_filter_taxonomy_index_tid/default/filter/tid', [], 'Expose filter');
    $edit = ['options[expose_button][checkbox][checkbox]' => TRUE];
    $this->submitForm($edit, 'Apply');
    $this->submitForm([], 'Save');
    // Make sure the unpublished term is shown to the admin user.
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
    $this->drupalLogout();
    $this->drupalGet('test-filter-taxonomy-index-tid');
    // Make sure the unpublished term isn't shown to the anonymous user.
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));

    // Tests that the term also isn't shown when not showing hierarchy.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'options[hierarchy]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/views/nojs/handler-extra/test_filter_taxonomy_index_tid/default/filter/tid', $edit, 'Apply');
    $this->submitForm([], 'Save');
    $this->drupalGet('test-filter-taxonomy-index-tid');
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
    $this->drupalLogout();
    $this->drupalGet('test-filter-taxonomy-index-tid');
    // Make sure the unpublished term isn't shown to the anonymous user.
    $this->assertNotEmpty($this->cssSelect('option[value="' . $this->terms[0][0]->id() . '"]'));
    $this->assertEmpty($this->cssSelect('option[value="' . $this->terms[1][0]->id() . '"]'));
  }

}
