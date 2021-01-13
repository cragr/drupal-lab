<?php

namespace Drupal\help_topics_twig_tester;

use Drupal\Core\Template\TwigNodeTrans;
use Twig\Environment;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\SetNode;
use Twig\Node\TextNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\NodeVisitor\AbstractNodeVisitor;

/**
 * Defines a Twig node visitor for testing help topics.
 */
class HelpTestTwigNodeVisitor extends AbstractNodeVisitor {

  /**
   * {@inheritdoc}
   */
  protected function doEnterNode(Node $node, Environment $env) {
    return $node;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLeaveNode(Node $node, Environment $env) {
    $processing = help_topics_twig_tester_get_values();
    if (!$processing['type']) {
      return $node;
    }

    // For all processing types, we want to remove variables, set statements,
    // and assorted Twig expression calls (if, do, etc.).
    if ($node instanceof SetNode || $node instanceof PrintNode ||
       $node instanceof AbstractExpression) {
      return NULL;
    }

    if ($node instanceof TwigNodeTrans) {
      // Count the number of translated chunks.
      $this_chunk = $processing['chunk_count'] + 1;
      help_topics_twig_tester_set_value('chunk_count', $this_chunk);
      if ($this_chunk > $processing['max_chunk']) {
        help_topics_twig_tester_set_value('max_chunk', $this_chunk);
      }

      if ($processing['type'] == 'remove_translated') {
        // Remove all translated text.
        return NULL;
      }
      else if ($processing['type'] == 'replace_translated') {
        // Replace with a dummy string.
        $node = new TextNode('dummy', 0);
      }
      else if ($processing['type'] == 'translated_chunk') {
        // Return the text only if it's the next chunk we're supposed to return.
        if ($this_chunk == $processing['return_chunk']) {
          return $node->getNode('body');
        }

        return NULL;
      }
    }

    if ($processing['type'] == 'remove_translated' && $node instanceof TextNode) {
      // For this processing, we also want to remove all HTML tags and
      // whitespace from TextNodes.
      $text = $node->getAttribute('data');
      $text = strip_tags($text);
      $text = preg_replace('|\s+|', '', $text);
      return new TextNode($text, 0);
    }

    return $node;
 }

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    return -100;
  }

}
