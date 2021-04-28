<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d7;

use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\file\Kernel\Migrate\d7\FileMigrationSetupTrait;

// cSpell:ignore Opaka

/**
 * Tests multi-value field value migration.
 *
 * @group migrate_drupal
 */
class MultiValueFieldMigrationsTest extends MigrateDrupal7TestBase {

  use FileMigrationSetupTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'comment',
    'datetime',
    'image',
    'language',
    'link',
    'menu_ui',
    // A requirement for translation migrations.
    'migrate_drupal_multilingual',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileMigrationSetup();

    $this->installEntitySchema('comment');
    $this->installSchema('node', ['node_access']);

    $this->migrateUsers();
    $this->migrateFields();
    $this->executeMigrations([
      'language',
      'd7_language_content_settings',
      'd7_taxonomy_vocabulary',
      'd7_node',
      'd7_node_translation',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileMigrationInfo() {
    return [
      'path' => 'public://sites/default/files/cube.jpeg',
      'size' => '3620',
      'base_path' => 'public://',
      'plugin_id' => 'd7_file',
    ];
  }

  /**
   * Tests multi-value field value migration.
   */
  public function testMultiValueFieldMigration() {
    $node1 = Node::load(1);
    $node1_array = $node1->toArray();
    // Remove "moving" properties.
    unset($node1_array['uuid']);
    $this->assertEquals([
      'nid' => [['value' => 1]],
      'vid' => [['value' => 1]],
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'test_content_type']],
      'revision_timestamp' => [['value' => '1441032132']],
      'revision_uid' => [['target_id' => '1']],
      'revision_log' => [],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => '2']],
      'title' => [['value' => 'An English Node']],
      'created' => [['value' => '1421727515']],
      'changed' => [['value' => '1441032132']],
      'promote' => [['value' => 1]],
      'sticky' => [['value' => 0]],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'content_translation_source' => [['value' => 'und']],
      'content_translation_outdated' => [['value' => 0]],
      'field_boolean' => [['value' => 1]],
      'field_email' => [
        0 => ['value' => 'default@example.com'],
        1 => ['value' => 'another@example.com'],
      ],
      'field_phone' => [['value' => '99-99-99-99']],
      'field_date' => [['value' => '2015-01-20T04:15:00']],
      'field_date_with_end_time' => [['value' => '1421727300']],
      'field_file' => [
        0 => [
          'target_id' => '2',
          'display' => '1',
          'description' => 'file desc',
        ],
      ],
      'field_float' => [['value' => 1]],
      'field_images' => [
        0 => [
          'target_id' => '1',
          'alt' => 'alt text',
          'title' => 'title text',
          'width' => '93',
          'height' => '93',
        ],
      ],
      'field_integer' => [['value' => 5]],
      'field_link' => [
        0 => [
          'uri' => 'http://google.com',
          'title' => 'Click Here',
          'options' => [
            'attributes' => ['title' => 'Click Here'],
          ],
        ],
      ],
      'field_text_list' => [['value' => 'Some more text']],
      'field_integer_list' => [['value' => '7']],
      'field_long_text' => [],
      'field_term_reference' => [['target_id' => '4']],
      'field_text' => [['value' => 'qwerty']],
      'field_node_entityreference' => [['target_id' => '2']],
      'field_user_entityreference' => [],
      'field_term_entityreference' => [
        0 => [
          'target_id' => '17',
        ],
        1 => [
          'target_id' => '15',
        ],
      ],
      'field_private_file' => [],
      'field_datetime_without_time' => [['value' => '2015-01-20']],
      'field_date_without_time' => [['value' => '2015-01-20']],
      'field_float_list' => [['value' => '3.1416']],
    ], $node1_array);

    $node2 = Node::load(2);
    assert($node2 instanceof TranslatableInterface);
    $node2_en_array = $node2->getTranslation('en')->toArray();
    // Remove "moving" properties.
    unset($node2_en_array['uuid']);
    $this->assertEquals([
      'nid' => [['value' => 2]],
      'vid' => [['value' => 11]],
      'langcode' => [['value' => 'en']],
      'type' => [['target_id' => 'article']],
      'revision_timestamp' => [['value' => '1564543706']],
      'revision_uid' => [['target_id' => '1']],
      'revision_log' => [['value' => 'is - DS9 2nd rev']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => '2']],
      'title' => [['value' => 'The thing about Deep Space 9']],
      'created' => [['value' => '1441306772']],
      'changed' => [['value' => '1564543637']],
      'promote' => [['value' => 1]],
      'sticky' => [['value' => 0]],
      'default_langcode' => [['value' => 1]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'content_translation_source' => [['value' => 'und']],
      'content_translation_outdated' => [['value' => 0]],
      'field_link' => [
        [
          'uri' => 'internal:/',
          'title' => 'Home',
          'options' => ['attributes' => []],
        ],
      ],
      'body' => [
        0 => [
          'value' => "...is that it's the absolute best show ever. Trust me, I would know.",
          'summary' => '',
          'format' => 'filtered_html',
        ],
      ],
      'field_image' => [],
      'field_reference' => [['target_id' => '5']],
      'field_reference_2' => [['target_id' => '5']],
      'field_tags' => [
        0 => [
          'target_id' => '9',
        ],
        1 => [
          'target_id' => '14',
        ],
        2 => [
          'target_id' => '17',
        ],
      ],
      'field_text_filtered' => [],
      'field_text_long_filtered' => [],
      'field_text_long_plain' => [['value' => 'DS9 2nd rev']],
      'field_text_plain' => [['value' => 'Kai Opaka']],
      'field_text_sum_filtered' => [],
      'field_vocab_fixed' => [['target_id' => '24']],
      'field_vocab_localize' => [['target_id' => '20']],
      'field_vocab_translate' => [['target_id' => '21']],
    ], $node2_en_array);

    $node2_is_array = $node2->getTranslation('is')->toArray();
    unset($node2_is_array['uuid']);
    $this->assertEquals([
      'nid' => [['value' => 2]],
      'vid' => [['value' => 11]],
      'langcode' => [['value' => 'is']],
      'type' => [['target_id' => 'article']],
      'revision_timestamp' => [['value' => '1564543706']],
      'revision_uid' => [['target_id' => '1']],
      'revision_log' => [['value' => 'is - DS9 2nd rev']],
      'status' => [['value' => 1]],
      'uid' => [['target_id' => '1']],
      'title' => [['value' => 'is - The thing about Deep Space 9']],
      'created' => [['value' => '1471428152']],
      'changed' => [['value' => '1564543706']],
      'promote' => [['value' => 1]],
      'sticky' => [['value' => 0]],
      'default_langcode' => [['value' => 0]],
      'revision_default' => [['value' => 1]],
      'revision_translation_affected' => [['value' => 1]],
      'content_translation_source' => [['value' => 'en']],
      'content_translation_outdated' => [['value' => 0]],
      'field_link' => [
        [
          'uri' => 'internal:/',
          'title' => 'Home',
          'options' => [
            'attributes' => [
              'title' => '',
            ],
          ],
        ],
      ],
      'body' => [
        0 => [
          'value' => "is - ...is that it's the absolute best show ever. Trust me, I would know.",
          'summary' => '',
          'format' => 'filtered_html',
        ],
      ],
      'field_image' => [],
      'field_reference' => [['target_id' => '4']],
      'field_reference_2' => [['target_id' => '4']],
      'field_tags' => [
        0 => [
          'target_id' => '9',
        ],
        1 => [
          'target_id' => '14',
        ],
        2 => [
          'target_id' => '17',
        ],
      ],
      'field_text_filtered' => [],
      'field_text_long_filtered' => [],
      'field_text_long_plain' => [['value' => 'is - DS9 2nd rev']],
      'field_text_plain' => [['value' => 'Kai Opaka']],
      'field_text_sum_filtered' => [],
      'field_vocab_fixed' => [],
      'field_vocab_localize' => [['target_id' => '20']],
      'field_vocab_translate' => [['target_id' => '23']],
    ], $node2_is_array);
  }

}
