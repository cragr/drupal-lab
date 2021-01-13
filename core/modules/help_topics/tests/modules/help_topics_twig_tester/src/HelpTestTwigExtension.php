<?php

namespace Drupal\help_topics_twig_tester;

use Drupal\help_topics_twig_tester\HelpTestTwigNodeVisitor;
use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

/**
 * Defines and registers Drupal Twig extensions for testing help topics.
 */
class HelpTestTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors() {
    return [
      new HelpTestTwigNodeVisitor(),
    ];
  }

}
