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
  }

  /**
   * Tests the link collection normalizer.
   */
  public function testNormalize() {
    // Get the edit form URL for the second test user.
    $edit_form_url = $this->testUsers[0]->toUrl('edit-form');
    $link_context = new ResourceObject(new CacheableMetadata(), new ResourceType('n/a', 'n/a', 'n/a'), 'n/a', NULL, [], new LinkCollection([]));
    $link_collection = (new LinkCollection([]))
      ->withLink('edit-form', new Link(new CacheableMetadata(), $edit_form_url, 'edit-form', ['title' => 'Edit']))
      ->withLink('related', new Link(new CacheableMetadata(), Url::fromUri('http://example.com/post/42'), 'related', ['title' => 'Most viewed']))
      ->withLink('related', new Link(new CacheableMetadata(), Url::fromUri('http://example.com/post/42'), 'related', ['title' => 'Top rated']))
      ->withContext($link_context);
    $expected = [
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
    ];
    // Injects the first test user as the current user into the SUT.
    $this->setCurrentUser($this->testUsers[1]);
    $normalizer = new LinkCollectionNormalizer($this->testUsers[1]);
    $normalizer->setSerializer($this->serializer);
    $actual = $normalizer->normalize($link_collection);
    $this->assertInstanceOf(CacheableNormalization::class, $actual);
    // Check that the link object keys are prefixed by "related".
    $normalized = $actual->getNormalization();
    foreach (array_keys($normalized) as $key) {
      $this->assertStringStartsWith('related', $key);
    }
    $this->assertTrue(in_array('user', $actual->getCacheContexts()));
    $this->assertSame($expected, array_values($normalized));
    // Injects the second test user as the current user into the SUT.
    $this->setCurrentUser($this->testUsers[0]);
    $normalizer = new LinkCollectionNormalizer($this->testUsers[0]);
    $normalizer->setSerializer($this->serializer);
    // Add the edit-form link to the expected output since it should not be
    // visible since the current user is the entity owner.
    $actual = $normalizer->normalize($link_collection);
    $this->assertInstanceOf(CacheableNormalization::class, $actual);
    $normalized = $actual->getNormalization();
    array_unshift($expected, [
      'href' => $edit_form_url->toString(),
      'meta' => [
        'title' => 'Edit',
      ],
    ]);
    $this->assertSame($expected, array_values($normalized));
    $this->assertTrue(in_array('user', $actual->getCacheContexts()));
  }

}
