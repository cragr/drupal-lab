<?php

/**
 * @file
 * Contains \Drupal\Tests\node\Unit\NodeTypePluralLabelTest.
 */

namespace Drupal\Tests\node\Unit;

use Drupal\Core\Config\Entity\EntityBundleWithPluralLabelsInterface;
use Drupal\Core\Config\Entity\EntityBundleWithPluralLabelsTrait;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Config\Entity\EntityBundleWithPluralLabelsTrait
 * @group node
 */
class NodeTypePluralLabelTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getSingularLabel
   * @covers ::getPluralLabel
   * @dataProvider providerForTestGetSingularAndPluralLabel
   *
   * @param string|null $entity_label
   *   The entity label.
   * @param string|null $singular_label
   *   The singular label.
   * @param string|null $expected_singular
   *   The expected singular label.
   * @param string|null $plural_label
   *   The plural label.
   * @param string|null $expected_plural
   *   The expected plural label.
   */
  public function testGetSingularAndPluralLabel($entity_label, $singular_label, $expected_singular, $plural_label, $expected_plural) {
    $bundle_entity_mock = new TestingBundleMock($entity_label, $singular_label, $plural_label, NULL);
    $this->assertEquals($expected_singular, $bundle_entity_mock->getSingularLabel());
    $this->assertEquals($expected_plural, $bundle_entity_mock->getPluralLabel());
  }

  /**
   * Provides test cases for self::testGetSingularAndPluralLabel().
   *
   * @return array
   *   Test cases.
   */
  public function providerForTestGetSingularAndPluralLabel() {
    return [
      // No singular/plural labels and a fallback cannot be built.
      'no labels' => [NULL, NULL, NULL, NULL, NULL],
      // No singular/plural labels but a fallback label could be built.
      'entity label only' => ['Eye', NULL, 'eye', NULL, 'eye items'],
      // No singular label but a fallback singular label could be built.
      'no singular label' => ['Eye', NULL, 'eye', 'eyes', 'eyes'],
      // No plural label but a fallback plural label could be built.
      'no plural label' => ['Eye', 'eye', 'eye', NULL, 'eye items'],
      // Singular and plural labels were provided.
      'singular/plural labels' => ['Eye', 'eye', 'eye', 'eyes', 'eyes'],
    ];
  }

  /**
   * @covers ::getCountLabel
   * @dataProvider providerForTestGetCountLabel
   *
   * @param array[] $count_labels
   *   The count label array.
   * @param string|null $entity_label
   *   The entity label.
   * @param string|null $singular_label
   *   The singular label.
   * @param string|null $plural_label
   *   The plural label.
   * @param array $expectation
   *   An array of associative arrays where each value is the expected result
   *   given a count integer which is the item key.
   */
  public function testGetCountLabel(array $count_labels, $entity_label, $singular_label, $plural_label, array $expectation) {
    $bundle_entity_mock = new TestingBundleMock($entity_label, $singular_label, $plural_label, $count_labels);
    foreach ($count_labels as $context => $count_label) {
      foreach ($expectation[$context] as $count => $expected) {
        // Count label doesn't have a context.
        if (!$context) {
          $this->assertEquals($expected, $bundle_entity_mock->getCountLabel($count));
        }
        // Multiple contextualised count labels.
        else {
          $this->assertEquals($expected, $bundle_entity_mock->getCountLabel($count, $context));
        }
      }
    }
  }

  /**
   * Provides test cases for self::testGetCountLabel().
   *
   * @return array
   *   Test cases.
   */
  public function providerForTestGetCountLabel() {
    return [
      // No singular/plural labels and a fallback cannot be build.
      'no labels' => [[NULL], NULL, NULL, NULL, [[1 => NULL, 2 => NULL]]],
      // The entity label is used to create fallback singular & plural labels
      // and these are used to create the count label fallback.
      'entity label only' => [
        [NULL],
        'Eye',
        NULL,
        NULL,
        [[1 => '1 eye', 2 => '2 eye items']],
      ],
      // In there's no count label set and one of singular or plural is missed,
      // it's not possible to create a count fallback label.
      'singular label only' => [
        [NULL],
        NULL,
        'eye',
        NULL,
        [[1 => NULL, 2 => NULL]],
      ],
      'only singular/plural labels' => [
        [NULL],
        NULL,
        'blue eye',
        'blue eyes',
        [[1 => '1 blue eye', 2 => '2 blue eyes']],
      ],
      'count label' => [
        [
          "1 blue eye\x3@count blue eyes",
        ],
        NULL,
        NULL,
        NULL,
        [[1 => '1 blue eye', 2 => '2 blue eyes']],
      ],
      // This count label lacks the singular variant.
      'broken count label, no singular variant' => [
        [
          "\x3@count blue eyes",
        ],
        NULL,
        NULL,
        NULL,
        [[1 => NULL, 2 => '2 blue eyes']],
      ],
      // This count label lacks the plural variant but is able to compute a
      // fallback from the entity label.
      'broken count label, no plural variant but with entity label' => [
        [
          "1 blue eye",
        ],
        'Eye',
        NULL,
        NULL,
        [[1 => '1 blue eye', 2 => '2 eye items']],
      ],
      // This count label lacks the plural variant but is able to compute a
      // fallback from the singular label.
      'broken count label, no plural variant but with singular label' => [
        [
          "1 blue eye",
        ],
        'Eye',
        'blue eye',
        'blue eyes',
        [[1 => '1 blue eye', 2 => '2 blue eyes']],
      ],
      // Multiple count labels.
      'contextualized count labels' => [
        [
          'default' => "1 blue eye\x3@count blue eyes",
          'items found' => "1 blue eye was found\x3@count blue eyes were found",
          'no count' => "blue eye\x3" . 'blue eyes',
          'with markup' => "<span>1</span> blue eye\x3<span>@count</span> blue eyes",
          'with parenthesis' => "blue eye\x3" . 'blue eyes (@count)',
        ],
        NULL,
        NULL,
        NULL,
        [
          'default' => [1 => '1 blue eye', 2 => '2 blue eyes'],
          'items found' => [1 => '1 blue eye was found', 2 => '2 blue eyes were found'],
          'no count' => [1 => 'blue eye', 2 => 'blue eyes'],
          'with markup' => [1 => '<span>1</span> blue eye', 2 => '<span>2</span> blue eyes'],
          'with parenthesis' => [1 => 'blue eye', 2 => 'blue eyes (2)'],
        ],
      ],
    ];
  }

}

// @codingStandardsIgnoreStart

/**
 * Mocks a bundle config entity class that uses the tested trait.
 */
class TestingBundleMock implements EntityBundleWithPluralLabelsInterface {

  use EntityBundleWithPluralLabelsTrait;

  protected $label;

  public function __construct($entity_label, $singular_label, $plural_label, $count_label) {
    $this->label = $entity_label;
    $this->label_singular = $singular_label;
    $this->label_plural = $plural_label;
    $this->label_count = $count_label;
  }

  public function label() { return $this->label; }
  public function language() { return new Language(); }
}

// @codingStandardsIgnoreEnd
