<?php

declare(strict_types = 1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\comment\Traits\CommentCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests threaded comments with depth limitation.
 *
 * @group comment
 */
class CommentThreadMaxDepthTest extends KernelTestBase {

  use CommentCreationTrait;
  use CommentTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'entity_test',
    'field',
    'system',
    'text',
    'user',
  ];

  /**
   * Testing comments structured by thread.
   *
   * @var array
   */
  protected $comment = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['comment']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->addDefaultCommentField('entity_test', 'entity_test');
  }

  /**
   * Tests threaded comments with depth limitation when replying is allowed.
   *
   * @covers \Drupal\comment\CommentViewBuilder::buildComponents
   */
  public function testMaxDepthReplyAllowed(): void {
    FieldConfig::loadByName('entity_test', 'entity_test', 'comment')
      ->setSetting('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED_DEPTH_LIMIT)
      ->setSetting('thread_limit', [
        'depth' => 3,
        'mode' => CommentItemInterface::THREAD_DEPTH_REPLY_MODE_ALLOW,
      ])->save();
    $this->createTestComments();

    /** @var \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder */
    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('comment');

    // Reply to 'deepest' comment.
    $reply = $this->createComment(['pid' => $this->comment[0][0][0]['entity']->id()]);
    // Reply to reply.
    $reply_to_reply = $this->createComment(['pid' => $reply->id()]);

    // The view builder is responsible to compute the indent for each comment.
    // @see \Drupal\comment\CommentViewBuilder::buildComponents()
    $build = $view_builder->viewMultiple([
      $this->comment[0]['entity'],
      $this->comment[0][0]['entity'],
      $this->comment[0][1]['entity'],
      $this->comment[0][0][0]['entity'],
      $reply,
      $reply_to_reply,
    ]);

    $build = $view_builder->buildMultiple($build);

    // Checking indents of each comment. Note that the build item
    // #comment_indent value is relative to the previous comment.
    $this->assertSame($this->comment[0]['entity']->id(), $build[0]['#comment']->id());
    $this->assertSame(0, $build[0]['#comment_indent']);
    $this->assertSame($this->comment[0][0]['entity']->id(), $build[1]['#comment']->id());
    $this->assertSame(1, $build[1]['#comment_indent']);
    $this->assertSame($this->comment[0][1]['entity']->id(), $build[2]['#comment']->id());
    $this->assertSame(0, $build[2]['#comment_indent']);
    $this->assertSame($this->comment[0][0][0]['entity']->id(), $build[3]['#comment']->id());
    $this->assertSame(1, $build[3]['#comment_indent']);
    // Check that the reply to deepest comment shows both with the same indent.
    $this->assertSame($reply->id(), $build[4]['#comment']->id());
    $this->assertSame(0, $build[4]['#comment_indent']);
    // Check that the reply to reply has the same indent.
    $this->assertSame($reply_to_reply->id(), $build[5]['#comment']->id());
    $this->assertSame(0, $build[5]['#comment_indent']);
  }

  /**
   * Tests threaded comments with depth limitation when replying is denied.
   *
   * @covers \Drupal\comment\CommentAccessControlHandler::checkCreateAccess
   */
  public function testMaxDepthReplyDenied(): void {
    FieldConfig::loadByName('entity_test', 'entity_test', 'comment')
      ->setSetting('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED_DEPTH_LIMIT)
      ->setSetting('thread_limit', [
        'depth' => 3,
        'mode' => CommentItemInterface::THREAD_DEPTH_REPLY_MODE_DENY,
      ])->save();
    $this->createTestComments();

    $account = $this->createUser(['view test entity', 'access comments']);
    $this->setCurrentUser($account);

    // Check that replying to comments not on the deepest level is allowed.
    $this->assertCommentHasReplyLink($this->comment[0]['entity']);
    $this->assertCommentHasReplyLink($this->comment[0][0]['entity']);
    $this->assertCommentHasReplyLink($this->comment[0][1]['entity']);
    // Check that replying to comments on the deepest level is denied.
    $this->assertCommentHasNotReplyLink($this->comment[0][0][0]['entity']);
  }

  /**
   * Asserts that the comment reply link exists within comment links.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   */
  protected function assertCommentHasReplyLink(CommentInterface $comment): void {
    $this->assertArrayHasKey('comment-reply', $this->getCommentLinks($comment));
  }

  /**
   * Asserts that the comment reply link doesn't exists within comment links.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   */
  protected function assertCommentHasNotReplyLink(CommentInterface $comment): void {
    $this->assertArrayNotHasKey('comment-reply', $this->getCommentLinks($comment));
  }

  /**
   * Returns the built comment links given a comment entity.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return array
   *   A list of built comment links.
   */
  protected function getCommentLinks(CommentInterface $comment): array {
    $lazy_builders = $this->container->get('comment.lazy_builders');
    $build = $lazy_builders->renderLinks($comment->id(), 'default', 'en', FALSE);
    return $build['comment']['#links'];
  }

  /**
   * Creates a threaded structure of comments.
   */
  protected function createTestComments(): void {
    $entity = EntityTest::create();
    $entity->save();

    $this->comment[0]['entity'] = $this->createComment([
     'entity_type' => 'entity_test',
     'entity_id' => $entity->id(),
    ]);
    $this->comment[0][0]['entity'] = $this->createComment([
      'pid' => $this->comment[0]['entity']->id(),
    ]);
    $this->comment[0][1]['entity'] = $this->createComment([
      'pid' => $this->comment[0]['entity']->id(),
    ]);
    // This is the 'deepest' comment.
    $this->comment[0][0][0]['entity'] = $this->createComment([
      'pid' => $this->comment[0][0]['entity']->id(),
    ]);
  }

}
