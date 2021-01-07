<?php

namespace Drupal\Tests\migrate_drupal\Kernel\Plugin\migrate\source;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\media\Entity\Media;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\ContentEntity;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the entity content source plugin.
 *
 * @group migrate_drupal
 */
class ContentEntityTest extends KernelTestBase {

  use EntityReferenceTestTrait;
  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'migrate',
    'migrate_drupal',
    'system',
    'node',
    'taxonomy',
    'field',
    'file',
    'image',
    'media',
    'media_test_source',
    'text',
    'filter',
    'language',
    'content_translation',
  ];

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'article';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected $fieldName = 'field_entity_reference';

  /**
   * The vocabulary ID.
   *
   * @var string
   */
  protected $vocabulary = 'fruit';

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The source plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrateSourcePluginManager
   */
  protected $sourcePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('file');
    $this->installEntitySchema('media');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', 'users_data');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);

    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Create article content type.
    $node_type = NodeType::create(['type' => $this->bundle, 'name' => 'Article']);
    $node_type->save();

    // Create a vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => $this->vocabulary,
      'description' => $this->vocabulary,
      'vid' => $this->vocabulary,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();

    // Create a term reference field on node.
    $this->createEntityReferenceField(
      'node',
      $this->bundle,
      $this->fieldName,
      'Term reference',
      'taxonomy_term',
      'default',
      ['target_bundles' => [$this->vocabulary]],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    // Create a term reference field on user.
    $this->createEntityReferenceField(
      'user',
      'user',
      $this->fieldName,
      'Term reference',
      'taxonomy_term',
      'default',
      ['target_bundles' => [$this->vocabulary]],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Create some data.
    $this->user = User::create([
      'name' => 'user123',
      'uid' => 1,
      'mail' => 'example@example.com',
    ]);
    $this->user->save();

    $term = Term::create([
      'vid' => $this->vocabulary,
      'name' => 'Apples',
      'uid' => $this->user->id(),
    ]);
    $term->save();
    $this->user->set($this->fieldName, $term->id());
    $this->user->save();
    $node = Node::create([
      'type' => $this->bundle,
      'title' => 'Apples',
      $this->fieldName => $term->id(),
      'uid' => $this->user->id(),
    ]);
    $node->save();
    $node->addTranslation('fr', [
      'title' => 'Pommes',
      $this->fieldName => $term->id(),
    ])->save();

    $this->sourcePluginManager = $this->container->get('plugin.manager.migrate.source');
    $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
  }

  /**
   * Tests the constructor for missing entity_type.
   */
  public function testConstructorEntityTypeMissing() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $configuration = [];
    $plugin_definition = [
      'entity_type' => '',
    ];
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('Missing required "entity_type" definition.');
    ContentEntity::create($this->container, $configuration, 'content_entity', $plugin_definition, $migration);
  }

  /**
   * Tests the constructor for non content entity.
   */
  public function testConstructorNonContentEntity() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $configuration = [];
    $plugin_definition = [
      'entity_type' => 'node_type',
    ];
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage('The entity type (node_type) is not supported. The "content_entity" source plugin only supports content entities.');
    ContentEntity::create($this->container, $configuration, 'content_entity:node_type', $plugin_definition, $migration);
  }

  /**
   * Tests the constructor for not bundleable entity.
   */
  public function testConstructorNotBundable() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $configuration = [
      'bundle' => 'foo',
    ];
    $plugin_definition = [
      'entity_type' => 'user',
    ];
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('A bundle was provided but the entity type (user) is not bundleable');
    ContentEntity::create($this->container, $configuration, 'content_entity:user', $plugin_definition, $migration);
  }

  /**
   * Tests the constructor for invalid entity bundle.
   */
  public function testConstructorInvalidBundle() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $configuration = [
      'bundle' => 'foo',
    ];
    $plugin_definition = [
      'entity_type' => 'node',
    ];
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('The provided bundle (foo) is not valid for the (node) entity type.');
    ContentEntity::create($this->container, $configuration, 'content_entity:node', $plugin_definition, $migration);
  }

  /**
   * Provide migration configuration, iterating all options.
   */
  public function migrationConfigurationProvider() {
    $data = [];
    foreach ([FALSE, TRUE] as $include_translations) {
      foreach ([FALSE, TRUE] as $include_revisions) {
        $configuration = [
          'include_translations' => $include_translations,
          'include_revisions' => $include_revisions,
        ];
        // That array key gives us nice test failure messages.
        $data[http_build_query($configuration)] = [$configuration];
      }
    }
    return $data;
  }

  /**
   * Provide migration configuration, iterating all options, including legacy.
   *
   * @ingroup legacy
   */
  public function migrationConfigurationLegacyProvider() {
    $data = [];
    foreach ($this->migrationConfigurationProvider() as $configurations) {
      $configuration = $configurations[0];
      foreach ([FALSE, TRUE] as $revisions_bc_mode) {
        $configuration['revisions_bc_mode'] = $revisions_bc_mode;
        // That array key gives us nice test failure messages.
        $data[http_build_query($configuration)] = [$configuration];
      }
    }
    return $data;
  }

  /**
   * Expect the revisions_bc_mode deprecation, if applicable.
   *
   * @ingroup legacy
   */
  protected function maybeExpectRevisionsBcModeDeprecation($configuration) {
    if ($configuration['revisions_bc_mode'] ?? TRUE) {
      $this->expectDeprecation('Calling ContentEntity migration with non-false revisions_bc_mode configuration parameter is deprecated in drupal:9.2.0 and is removed in drupal:10.0.0. Instead, you should set this parameter to false. See https://www.drupal.org/node/3191344');
    }
  }

  /**
   * Reusable helper to assert IDs structure.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The source plugin.
   * @param array $configuration
   *   The source plugin configuration (Nope, no getter available).
   */
  protected function assertCorrectIds(MigrateSourceInterface $source, array $configuration) {
    $ids = $source->getIds();
    list(, $entity_type_id) = explode(PluginBase::DERIVATIVE_SEPARATOR, $source->getPluginId());
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);

    $this->assertArrayHasKey($entity_type->getKey('id'), $ids);
    $ids_count_expected = 1;

    // Yes, langcode only depends on entity type, not source configuration.
    if ($entity_type->isTranslatable()) {
      $ids_count_expected++;
      $this->assertArrayHasKey($entity_type->getKey('langcode'), $ids);
    }

    $include_revision_key = ($configuration['include_revisions'] ?? FALSE)
      || ($configuration['revisions_bc_mode'] ?? TRUE);
    if ($entity_type->isRevisionable() && $include_revision_key) {
      $ids_count_expected++;
      $this->assertArrayHasKey($entity_type->getKey('revision'), $ids);
    }

    $this->assertEquals($ids_count_expected, count($ids));
  }

  /**
   * Tests user source plugin.
   *
   * Legacy group is needed to expect deprecation.
   * @group legacy
   *
   * @dataProvider migrationConfigurationLegacyProvider
   */
  public function testUserSource(array $configuration) {
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:user'));
    $this->maybeExpectRevisionsBcModeDeprecation($configuration);
    $user_source = $this->sourcePluginManager->createInstance('content_entity:user', $configuration, $migration);
    $this->assertSame('users', $user_source->__toString());
    // ::count is not yet functional for include_translations.
    if (!$configuration['include_translations']) {
      $this->assertEquals(1, $user_source->count());
    }
    $this->assertCorrectIds($user_source, $configuration);
    $fields = $user_source->fields();
    $this->assertArrayHasKey('name', $fields);
    $this->assertArrayHasKey('pass', $fields);
    $this->assertArrayHasKey('mail', $fields);
    $this->assertArrayHasKey('uid', $fields);
    $this->assertArrayHasKey('roles', $fields);
    $user_source->rewind();
    $values = $user_source->current()->getSource();
    $this->assertEquals('example@example.com', $values['mail'][0]['value']);
    $this->assertEquals('user123', $values['name'][0]['value']);
    $this->assertEquals(1, $values['uid']);
    $this->assertEquals(1, $values['field_entity_reference'][0]['target_id']);
  }

  /**
   * Tests file source plugin.
   *
   * Legacy group is needed to expect deprecation.
   * @group legacy
   *
   * @dataProvider migrationConfigurationLegacyProvider
   */
  public function testFileSource(array $configuration) {
    $file = File::create([
      'filename' => 'foo.txt',
      'uid' => $this->user->id(),
      'uri' => 'public://foo.txt',
    ]);
    $file->save();

    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:file'));
    $this->maybeExpectRevisionsBcModeDeprecation($configuration);
    $file_source = $this->sourcePluginManager->createInstance('content_entity:file', $configuration, $migration);
    $this->assertSame('files', $file_source->__toString());
    // ::count is not yet functional for include_translations.
    if (!$configuration['include_translations']) {
      $this->assertEquals(1, $file_source->count());
    }
    $this->assertCorrectIds($file_source, $configuration);
    $fields = $file_source->fields();
    $this->assertArrayHasKey('fid', $fields);
    $this->assertArrayHasKey('filemime', $fields);
    $this->assertArrayHasKey('filename', $fields);
    $this->assertArrayHasKey('uid', $fields);
    $this->assertArrayHasKey('uri', $fields);
    $file_source->rewind();
    $values = $file_source->current()->getSource();
    $this->assertEquals('text/plain', $values['filemime'][0]['value']);
    $this->assertEquals('public://foo.txt', $values['uri'][0]['value']);
    $this->assertEquals('foo.txt', $values['filename'][0]['value']);
    $this->assertEquals(1, $values['fid']);
  }

  /**
   * Tests node source plugin.
   *
   * Legacy group is needed to expect deprecation.
   * @group legacy
   *
   * @dataProvider migrationConfigurationLegacyProvider
   */
  public function testNodeSource(array $configuration) {
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:node'));
    $configuration += ['bundle' => $this->bundle];
    $this->maybeExpectRevisionsBcModeDeprecation($configuration);
    $node_source = $this->sourcePluginManager->createInstance('content_entity:node', $configuration, $migration);
    $this->assertSame('content items', $node_source->__toString());
    $this->assertCorrectIds($node_source, $configuration);
    $fields = $node_source->fields();
    $this->assertArrayHasKey('nid', $fields);
    $this->assertArrayHasKey('vid', $fields);
    $this->assertArrayHasKey('title', $fields);
    $this->assertArrayHasKey('uid', $fields);
    $this->assertArrayHasKey('sticky', $fields);
    $node_source->rewind();
    $values = $node_source->current()->getSource();
    $this->assertEquals($this->bundle, $values['type'][0]['target_id']);
    $this->assertEquals(1, $values['nid']);
    // IDs have no deltas.
    $expectRevisionWithNoDelta = $configuration['revisions_bc_mode']
      || $configuration['include_revisions'];
    if ($expectRevisionWithNoDelta) {
      $this->assertEquals(1, $values['vid']);
    }
    else {
      $this->assertEquals([0 => ['value' => 1]], $values['vid']);
    }
    $this->assertEquals('en', $values['langcode']);
    $this->assertEquals(1, $values['status'][0]['value']);
    $this->assertEquals('Apples', $values['title'][0]['value']);
    $this->assertEquals(1, $values['default_langcode'][0]['value']);
    $this->assertEquals(1, $values['field_entity_reference'][0]['target_id']);
    if ($configuration['include_translations']) {
      $node_source->next();
      $values = $node_source->current()->getSource();
      $this->assertEquals($this->bundle, $values['type'][0]['target_id']);
      $this->assertEquals(1, $values['nid']);
      if ($expectRevisionWithNoDelta) {
        $this->assertEquals(1, $values['vid']);
      }
      else {
        $this->assertEquals([0 => ['value' => 1]], $values['vid']);
      }
      $this->assertEquals('fr', $values['langcode']);
      $this->assertEquals(1, $values['status'][0]['value']);
      $this->assertEquals('Pommes', $values['title'][0]['value']);
      $this->assertEquals(0, $values['default_langcode'][0]['value']);
      $this->assertEquals(1, $values['field_entity_reference'][0]['target_id']);
    }
  }

  /**
   * Tests media source plugin.
   *
   * Legacy group is needed to expect deprecation.
   * @group legacy
   *
   * @dataProvider migrationConfigurationLegacyProvider
   */
  public function testMediaSource(array $configuration) {
    $values = [
      'id' => 'image',
      'label' => 'Image',
      'source' => 'test',
      'new_revision' => FALSE,
    ];
    $media_type = $this->createMediaType('test', $values);
    $media = Media::create([
      'name' => 'Foo media',
      'uid' => $this->user->id(),
      'bundle' => $media_type->id(),
    ]);
    $media->save();

    $configuration += [
      'bundle' => 'image',
    ];
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:media'));
    $this->maybeExpectRevisionsBcModeDeprecation($configuration);
    $media_source = $this->sourcePluginManager->createInstance('content_entity:media', $configuration, $migration);
    $this->assertSame('media items', $media_source->__toString());
    // ::count is not yet functional for include_translations.
    if (!$configuration['include_translations']) {
      $this->assertEquals(1, $media_source->count());
    }
    $this->assertCorrectIds($media_source, $configuration);
    $fields = $media_source->fields();
    $this->assertArrayHasKey('bundle', $fields);
    $this->assertArrayHasKey('mid', $fields);
    $this->assertArrayHasKey('vid', $fields);
    $this->assertArrayHasKey('name', $fields);
    $this->assertArrayHasKey('status', $fields);
    $media_source->rewind();
    $values = $media_source->current()->getSource();
    $this->assertEquals(1, $values['mid']);
    // IDs have no deltas.
    $expectRevisionWithNoDelta = $configuration['revisions_bc_mode']
      || $configuration['include_revisions'];
    if ($expectRevisionWithNoDelta) {
      $this->assertEquals(1, $values['vid']);
    }
    else {
      $this->assertEquals([0 => ['value' => 1]], $values['vid']);
    }
    $this->assertEquals('Foo media', $values['name'][0]['value']);
    $this->assertNull($values['thumbnail'][0]['title']);
    $this->assertEquals(1, $values['uid'][0]['target_id']);
    $this->assertEquals('image', $values['bundle'][0]['target_id']);
  }

  /**
   * Tests term source plugin.
   *
   * Legacy group is needed to expect deprecation.
   * @group legacy
   *
   * @dataProvider migrationConfigurationLegacyProvider
   */
  public function testTermSource(array $configuration) {
    $term2 = Term::create([
      'vid' => $this->vocabulary,
      'name' => 'Granny Smith',
      'uid' => $this->user->id(),
      'parent' => 1,
    ]);
    $term2->save();

    $configuration += [
      'bundle' => $this->vocabulary,
    ];
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition('content_entity:taxonomy_term'));
    $this->maybeExpectRevisionsBcModeDeprecation($configuration);
    $term_source = $this->sourcePluginManager->createInstance('content_entity:taxonomy_term', $configuration, $migration);
    $this->assertSame('taxonomy terms', $term_source->__toString());
    // ::count is not yet functional for include_translations.
    if (!$configuration['include_translations']) {
      $this->assertEquals(2, $term_source->count());
    }
    $this->assertCorrectIds($term_source, $configuration);
    $fields = $term_source->fields();
    $this->assertArrayHasKey('vid', $fields);
    $this->assertArrayHasKey('revision_id', $fields);
    $this->assertArrayHasKey('tid', $fields);
    $this->assertArrayHasKey('name', $fields);
    $term_source->rewind();
    $values = $term_source->current()->getSource();
    $this->assertEquals($this->vocabulary, $values['vid'][0]['target_id']);
    $this->assertEquals(1, $values['tid']);
    // @TODO: Add test coverage for parent in
    // https://www.drupal.org/project/drupal/issues/2940198
    $this->assertEquals('Apples', $values['name'][0]['value']);
    $term_source->next();
    $values = $term_source->current()->getSource();
    $this->assertEquals($this->vocabulary, $values['vid'][0]['target_id']);
    $this->assertEquals(2, $values['tid']);
    // @TODO: Add test coverage for parent in
    // https://www.drupal.org/project/drupal/issues/2940198
    $this->assertEquals('Granny Smith', $values['name'][0]['value']);
  }

  /**
   * Get a migration definition.
   *
   * @param string $plugin_id
   *   The plugin id.
   *
   * @return array
   *   The definition.
   */
  protected function migrationDefinition($plugin_id) {
    return [
      'source' => [
        'plugin' => $plugin_id,
      ],
      'process' => [],
      'destination' => [
        'plugin' => 'null',
      ],
    ];
  }

}
