<?php

namespace Drupal\Tests\comment\Unit;

use Drupal\comment\CommentManager;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\history\HistoryRepositoryInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\comment\CommentManager
 * @group comment
 */
class CommentManagerTest extends UnitTestCase {

  /**
   * Tests the getFields method.
   *
   * @covers ::getFields
   */
  public function testGetFields() {
    // Set up a content entity type.
    $entity_type = $this->createMock('Drupal\Core\Entity\ContentEntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getClass')
      ->will($this->returnValue('Node'));
    $entity_type->expects($this->any())
      ->method('entityClassImplements')
      ->with(FieldableEntityInterface::class)
      ->will($this->returnValue(TRUE));

    $entity_field_manager = $this->createMock(EntityFieldManagerInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $history_repository = $this->createMock(HistoryRepositoryInterface::class);

    $entity_field_manager->expects($this->once())
      ->method('getFieldMapByFieldType')
      ->will($this->returnValue([
        'node' => [
          'field_foobar' => [
            'type' => 'comment',
          ],
        ],
      ]));

    $entity_type_manager->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($entity_type));

    $history_repository->expects($this->any())
      ->method('getLastViewed')
      ->willReturn([]);

    $comment_manager = new CommentManager(
      $entity_type_manager,
      $this->createMock('Drupal\Core\Config\ConfigFactoryInterface'),
      $this->createMock('Drupal\Core\StringTranslation\TranslationInterface'),
      $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface'),
      $this->createMock(AccountInterface::class),
      $entity_field_manager,
      $this->prophesize(EntityDisplayRepositoryInterface::class)->reveal(),
      $history_repository
    );
    $comment_fields = $comment_manager->getFields('node');
    $this->assertArrayHasKey('field_foobar', $comment_fields);
  }

}
