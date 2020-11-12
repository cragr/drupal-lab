<?php

declare(strict_types = 1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\CommentInterface;
use Drupal\comment\CommentManagerInterface;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Render\Element;
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
   * Tests threaded comments with no depth limitation.
   *
   * @covers \Drupal\comment\CommentViewBuilder::buildComponents
   */
  public function testNoMaxDepth(): void {
    // Check that we're using not limited comment threading.
    $this->assertSame(CommentManagerInterface::COMMENT_MODE_THREADED, FieldConfig::loadByName('entity_test', 'entity_test', 'comment')->getSetting('default_mode'));

    $this->createTestComments();

    /** @var \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder */
    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('comment');

    // Reply to 'deepest' comment.
    $reply = $this->createComment(['pid' => $this->comment[0][0][0]['entity']->id()]);
    // Reply to reply.
    $reply_to_reply = $this->createComment(['pid' => $reply->id()]);
    // Reply to reply of reply.
    $reply_to_reply_of_reply = $this->createComment(['pid' => $reply_to_reply->id()]);

    // The view builder is responsible to compute the indent for each comment.
    // @see \Drupal\comment\CommentViewBuilder::buildComponents()
    $build = $view_builder->viewMultiple([
      $this->comment[0]['entity'],
      $this->comment[0][0]['entity'],
      $this->comment[0][1]['entity'],
      $this->comment[0][0][0]['entity'],
      $reply,
      $reply_to_reply,
      $reply_to_reply_of_reply,
    ]);
    $build = $view_builder->buildMultiple($build);

    // Checking indents of each comment. Note that the build item
    // #comment_indent value is relative to the previous comment.
    $this->assertCommentIndent($this->comment[0]['entity'], 0, $build);
    $this->assertCommentIndent($this->comment[0][0]['entity'], 1, $build);
    $this->assertCommentIndent($this->comment[0][1]['entity'], 0, $build);
    $this->assertCommentIndent($this->comment[0][0][0]['entity'], 1, $build);
    $this->assertCommentIndent($reply, 1, $build);
    $this->assertCommentIndent($reply_to_reply, 1, $build);
    $this->assertCommentIndent($reply_to_reply_of_reply, 1, $build);
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
    $this->assertCommentIndent($this->comment[0]['entity'], 0, $build);
    $this->assertCommentIndent($this->comment[0][0]['entity'], 1, $build);
    $this->assertCommentIndent($this->comment[0][1]['entity'], 0, $build);
    $this->assertCommentIndent($this->comment[0][0][0]['entity'], 1, $build);
    // Check that the reply to deepest comment shows both with the same indent.
    $this->assertCommentIndent($reply, 0, $build);
    // Check that the reply to reply has the same indent.
    $this->assertCommentIndent($reply_to_reply, 0, $build);

    // Change the reply mode in order to test that, when reply to the deepest
    // comment is denied, the thread still shows with the limited depth.
    FieldConfig::loadByName('entity_test', 'entity_test', 'comment')
      ->setSetting('default_mode', CommentManagerInterface::COMMENT_MODE_THREADED_DEPTH_LIMIT)
      ->setSetting('thread_limit', [
        'depth' => 3,
        'mode' => CommentItemInterface::THREAD_DEPTH_REPLY_MODE_DENY,
      ])->save();

    // Rebuild comments.
    $build = $view_builder->viewMultiple([
      $this->comment[0]['entity'],
      $this->comment[0][0]['entity'],
      $this->comment[0][1]['entity'],
      $this->comment[0][0][0]['entity'],
      $reply,
      $reply_to_reply,
    ]);
    $build = $view_builder->buildMultiple($build);

    // Checking that the indents are kept.
    $this->assertCommentIndent($this->comment[0]['entity'], 0, $build);
    $this->assertCommentIndent($this->comment[0][0]['entity'], 1, $build);
    $this->assertCommentIndent($this->comment[0][1]['entity'], 0, $build);
    $this->assertCommentIndent($this->comment[0][0][0]['entity'], 1, $build);
    // Check that the reply to deepest comment shows both with the same indent.
    $this->assertCommentIndent($reply, 0, $build);
    // Check that the reply to reply has the same indent.
    $this->assertCommentIndent($reply_to_reply, 0, $build);
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
   * Asserts that a comment has an expected indent within a build.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment to be checked.
   * @param int $expected_indent
   *   The expected comment indent. Note that the comment indent value is
   *   relative to the previous comment.
   * @param array $build
   *   A render array that renders a list of comments.
   */
  protected function assertCommentIndent(CommentInterface $comment, int $expected_indent, array $build): void {
    foreach (Element::children($build) as $delta) {
      if ($build[$delta]['#comment']->id() === $comment->id()) {
        $this->assertSame($expected_indent, $build[$delta]['#comment_indent']);
        return;
      }
    }
    $this->fail("Comment with ID {$comment->id()} not found in the build.");
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
