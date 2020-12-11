<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Component\Utility\Html;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Twig debug markup.
 *
 * @group Theme
 */
class TwigDebugMarkupTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['theme_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    // Enable Twig debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();
  }

  /**
   * Helper function to convert a render array to markup.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   *
   * @return string
   *   The rendered markup.
   */
  public function render(array $elements) {
    /* @var \Drupal\Core\Render\Renderer $renderer */
    $renderer = $this->container->get('renderer');
    return $renderer->renderRoot($elements);
  }

  /**
   * Tests debug markup is on and off.
   */
  public function testDebugMarkup() {
    $extension = twig_extension();

    // Find full path to template.
    $cache = $this->container->get('theme.registry')->get();
    $templates = drupal_find_theme_templates($cache, $extension, drupal_get_path('module', 'theme_test'));
    $template_filename = $templates['theme_test_specific_suggestions']['path'] . '/' . $templates['theme_test_specific_suggestions']['template'] . $extension;

    // Render a template.
    $build = [
      '#theme' => 'theme_test_specific_suggestions',
    ];
    $output = $this->render($build);

    $expected = '<!-- THEME DEBUG -->';
    $this->assertStringContainsString($expected, $output, 'Twig debug markup found in theme output when debug is enabled.');

    $expected = "\n<!-- THEME HOOK: 'theme_test_specific_suggestions' -->";
    $this->assertStringContainsString($expected, $output, 'Theme hook comment found.');

    $expected = "\n<!-- BEGIN OUTPUT from '" . Html::escape($template_filename) . "' -->\n";
    $this->assertStringContainsString($expected, $output, 'Full path to current template file found in BEGIN OUTPUT comment.');
    $expected = "\n<!-- END OUTPUT from '" . Html::escape($template_filename) . "' -->\n";
    $this->assertStringContainsString($expected, $output, 'Full path to current template file found in END OUTPUT comment.');

    // Disable Twig debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    // Re-render the template.
    $output = $this->render($build);

    $expected = 'Template for testing specific theme calls.';
    $this->assertStringContainsString($expected, $output, 'Confirm template is still rendered.');
    $unexpected = '<!-- THEME DEBUG -->';
    $this->assertStringNotContainsString($unexpected, $output, 'Twig debug markup not found in theme output when debug is disabled.');
  }

  /**
   * Tests file name suggestions comment.
   */
  public function testFileNameSuggestions() {
    $extension = twig_extension();

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
    $expected = '   * theme-test-specific-suggestions--variant' . $extension . PHP_EOL
      . '   x theme-test-specific-suggestions' . $extension . PHP_EOL
      . '-->';
    $this->assertStringContainsString($expected, $output, 'Multiple suggestions should have file name suggestions listed.');
  }

  /**
   * Tests suggestions when file name does not match.
   */
  public function testFileNameNotMatchingSuggestion() {
    $extension = twig_extension();

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

    // Note: this test is documenting the results of a bug.
    $unexpected = '   x theme_test.template_test' . $extension . PHP_EOL;
    $this->assertStringNotContainsString($unexpected, $output, 'The actual template file name is not used when it does not match the suggestion.');

    $expected = "\n<!-- BEGIN OUTPUT from '" . Html::escape($template_filename) . "' -->\n";
    $this->assertStringContainsString($expected, $output, 'Full path to current template file found in BEGIN OUTPUT comment.');
  }

  /**
   * Tests XSS attempt in theme suggestions and Twig debug comments.
   */
  public function testXssComments() {
    $extension = twig_extension();

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
