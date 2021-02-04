<?php

namespace Drupal\Tests\jsonapi\Kernel\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\LinkCollectionNormalizer;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\LinkCollectionNormalizer
 * @group jsonapi
 *
 * @internal
 */
class LinkCollectionNormalizerTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The subject under test.
   *
   * @var \Drupal\jsonapi\Normalizer\LinkCollectionNormalizer
   */
  protected $normalizer;

  /**
   * Test users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $testUsers;

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'jsonapi',
    'serialization',
    'system',
    'user',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add the entity schemas.
    $this->installEntitySchema('user');
    // Add the additional table schemas.
    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    // Set the user IDs to something higher than 1 so these users cannot be
    // mistaken for the site admin.
    $this->testUsers[] = $this->createUser([], NULL, FALSE, ['uid' => 2]);
    $this->testUsers[] = $this->createUser([], NULL, FALSE, ['uid' => 3]);
    $this->serializer = $this->container->get('jsonapi.serializer');
    // Create the SUT.
    $this->normalizer = new LinkCollectionNormalizer();
    $this->normalizer->setSerializer($this->serializer);
  }

  /**
   * Tests the link collection normalizer.
   */
  public function testNormalize() {
    $link_context = new ResourceObject(new CacheableMetadata(), new ResourceType('n/a', 'n/a', 'n/a'), 'n/a', NULL, [], new LinkCollection([]));
    $link_collection = (new LinkCollection([]))
      ->withLink('related', new Link(new CacheableMetadata(), Url::fromUri('http://example.com/post/42'), 'related', ['title' => 'Most viewed']))
      ->withLink('related', new Link(new CacheableMetadata(), Url::fromUri('http://example.com/post/42'), 'related', ['title' => 'Top rated']))
      ->withContext($link_context);
    $normalized = $this->normalizer->normalize($link_collection)->getNormalization();
    $this->assertIsArray($normalized);
    foreach (array_keys($normalized) as $key) {
      $this->assertStringStartsWith('related', $key);
    }
    $this->assertSame([
      [
        'href' => 'http://example.com/post/42',
        'meta' => [
          'title' => 'Most viewed',
        ],
      ],
      [
        'href' => 'http://example.com/post/42',
        'meta' => [
          'title' => 'Top rated',
        ],
      ],
    ], array_values($normalized));
  }

  /**
   * Tests the link collection normalizer.
   *
   * @dataProvider linkAccessTestData
   */
  public function testLinkAccess($current_user_id, $edit_form_uid, $expected_link_keys, $expected_cache_contexts) {
    // Get the current user and an edit-form URL.
    foreach ($this->testUsers as $user) {
      $uid = (int) $user->id();
      if ($uid === $current_user_id) {
        $current_user = $user;
      }
      if ($uid === $edit_form_uid) {
        $edit_form_url = $user->toUrl('edit-form');
      }
    }
    assert(isset($current_user));
    assert(isset($edit_form_url));

    // Set the current user. The current user is used to check access to
    // resources targeted by the link collection.
    $this->setCurrentUser($current_user);

    // Create a link collection to normalize.
    $mock_resource_object = $this->createMock(ResourceObject::class);
    $link_collection = new LinkCollection([
      'edit-form' => new Link(new CacheableMetadata(), $edit_form_url, 'edit-form', ['title' => 'Edit']),
    ]);
    $link_collection = $link_collection->withContext($mock_resource_object);

    // Normalize the collection.
    $actual_normalization = $this->normalizer->normalize($link_collection);

    // Check that it returned the expected value object.
    $this->assertInstanceOf(CacheableNormalization::class, $actual_normalization);

    // Get the raw normalized data.
    $actual_data = $actual_normalization->getNormalization();
    $this->assertIsArray($actual_data);

    // Check that the expected links are present and unexpected links are
    // present.
    $actual_link_keys = array_keys($actual_data);
    sort($expected_link_keys);
    sort($actual_link_keys);
    $this->assertSame($expected_link_keys, $actual_link_keys);

    // Check that the expected cache contexts were added.
    $actual_cache_contexts = $actual_normalization->getCacheContexts();
    sort($expected_cache_contexts);
    sort($actual_cache_contexts);
    $this->assertSame($expected_cache_contexts, $actual_cache_contexts);

    // If the edit-form link was present, check that it has the correct href.
    if (isset($actual_data['edit-form'])) {
      $this->assertSame($actual_data['edit-form'], [
        'href' => $edit_form_url->setAbsolute()->toString(),
        'meta' => [
          'title' => 'Edit',
        ],
      ]);
    }
  }

  public function linkAccessTestData() {
    return [
      'uid  2 can access the edit-form link because the account has permission to edit itself' => [
        'uid' => 2,
        'edit-form uid' => 2,
        'expected link keys' => ['edit-form'],
        'expected cache contexts' => ['user'],
      ],
      "uid  3 cannot access the edit-form link because the account doesn't have permission to edit another account" => [
        'uid' => 3,
        'edit-form uid' => 2,
        'expected link keys' => [],
        'expected cache contexts' => ['user'],
      ],
    ];
  }

}
