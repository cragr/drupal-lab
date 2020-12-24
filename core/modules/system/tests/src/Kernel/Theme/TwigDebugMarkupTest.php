<?php

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for Twig debug markup.
 *
 * @group Theme
 */
class TwigDebugMarkupTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'theme_test'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Enable Twig debugging.
    $parameters = $container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $container->setParameter('twig.config', $parameters);
  }

  /**
   * Helper function to convert a render array to markup.
   *
   * We override KernalTestBase::render() so that it outputs Twig debug comments
   * only for the render array given in a test and not for an entire page.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   *
   * @return string
   *   The rendered markup.
   *
   * @throws \Exception
   */
  public function render(array &$elements): string {
    /** @var \Drupal\Core\Render\Renderer $renderer */
    $renderer = $this->container->get('renderer');
    return $renderer->renderRoot($elements);
  }

  /**
   * Tests debug markup is on.
   *
   * @throws \Exception
   */
  public function testDebugMarkup() {
    $extension = '.html.twig';
    $hook = 'theme_test_specific_suggestions';
    $build = [
      '#theme' => $hook,
    ];

    // Find full path to template.
    $cache = $this->container->get('theme.registry')->get();
    $templates = drupal_find_theme_templates($cache, $extension, drupal_get_path('module', 'theme_test'));
    $template_filename = $templates[$hook]['path'] . '/' . $templates[$hook]['template'] . $extension;

    // Render a template.
    $output = $this->render($build);

    $expected = '<!-- THEME DEBUG -->';
    $this->assertStringContainsString($expected, $output, 'Twig debug markup found in theme output when debug is enabled.');

    $expected = "\n<!-- THEME HOOK: '$hook' -->";
    $this->assertStringContainsString($expected, $output, 'Theme hook comment found.');

    $expected = "\n<!-- BEGIN OUTPUT from '" . Html::escape($template_filename) . "' -->\n";
    $this->assertStringContainsString($expected, $output, 'Full path to current template file found in BEGIN OUTPUT comment.');
    $expected = "\n<!-- END OUTPUT from '" . Html::escape($template_filename) . "' -->\n";
    $this->assertStringContainsString($expected, $output, 'Full path to current template file found in END OUTPUT comment.');
  }

  /**
   * Tests file name suggestions comment.
   *
   * @throws \Exception
   */
  public function testFileNameSuggestions() {
    $extension = '.html.twig';

    // Render a template using a single suggestion.
    $build = [
      '#theme' => 'theme_test_specific_suggestions',
    ];
    $output = $this->render($build);

    $expected = "\n<!-- THEME HOOK: 'theme_test_specific_suggestions' -->";
    $this->assertStringContainsString($expected, $output, 'Theme hook comment found.');
    $unexpected = '<!-- FILE NAME SUGGESTIONS:';
    $this->assertStringNotContainsString($unexpected, $output, 'A single suggestion should not have file name suggestions listed.');

    // Render a template using multiple suggestions.
    $build = [
      '#theme' => 'theme_test_specific_suggestions__variant',
    ];
    $output = $this->render($build);

    $expected = "\n<!-- THEME HOOK: 'theme_test_specific_suggestions__variant' -->";
    $this->assertStringContainsString($expected, $output, 'Theme hook comment found.');
    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * theme-test-specific-suggestions--variant' . $extension . PHP_EOL
      . '   x theme-test-specific-suggestions' . $extension . PHP_EOL
      . '-->';
    $this->assertStringContainsString($expected, $output, 'Multiple suggestions should have file name suggestions listed.');
  }

  /**
   * Tests suggestions when file name does not match.
   *
   * @throws \Exception
   */
  public function testFileNameNotMatchingSuggestion() {
    $extension = '.html.twig';

    // Find full path to template.
    $cache = $this->container->get('theme.registry')->get();
    $templates = drupal_find_theme_templates($cache, $extension, drupal_get_path('module', 'theme_test'));
    $template_filename = $templates['theme_test_template_test']['path'] . '/' . $templates['theme_test_template_test']['template'] . $extension;

    // Render a template that doesn't match its suggestion name.
    $build = [
      '#theme' => 'theme_test_template_test__variant',
    ];
    $output = $this->render($build);

    $expected = "\n<!-- THEME HOOK: 'theme_test_template_test__variant' -->";
    $this->assertStringContainsString($expected, $output, 'Theme hook comment found.');

    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * theme-test-template-test--variant' . $extension . PHP_EOL
      . '   x theme_test.template_test' . $extension . PHP_EOL
      . '-->';
    $this->assertStringContainsString($expected, $output, 'The actual template file name should be used when it does not match the suggestion.');

    $expected = "\n<!-- BEGIN OUTPUT from '" . Html::escape($template_filename) . "' -->\n";
    $this->assertStringContainsString($expected, $output, 'Full path to current template file found in BEGIN OUTPUT comment.');
  }

  /**
   * Tests XSS attempt in theme suggestions and Twig debug comments.
   *
   * @throws \Exception
   */
  public function testXssComments() {
    $extension = '.html.twig';

    // Render a template whose suggestions have been compromised.
    $build = [
      '#theme' => 'theme_test_xss_suggestion',
    ];
    $output = $this->render($build);

    // @see theme_test_theme_suggestions_node()
    $xss_suggestion = Html::escape('theme-test-xss-suggestion--<script type="text/javascript">alert(\'yo\');</script>') . $extension;

    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * ' . $xss_suggestion . PHP_EOL
      . '   x theme-test-xss-suggestion' . $extension . PHP_EOL
      . '-->';
    $this->assertStringContainsString($expected, $output, 'XSS suggestion successfully escaped in Twig debug comments.');
    $this->assertStringContainsString('Template for testing XSS in theme hook suggestions.', $output, 'Base hook suggestion used instead of XSS suggestion.');
  }

}
