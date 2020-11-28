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
  protected static $modules = ['theme_test', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Shared renderer for each test.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();
    $this->drupalCreateContentType(['type' => 'page']);

    // Enable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Tests debug markup added to Twig template output.
   */
  public function testTwigDebugMarkup() {
    $extension = twig_extension();
    $xss_suggestion = Html::escape('node--<script type="text/javascript">alert(\'yo\');</script>') . $extension;
    $cache = $this->container->get('theme.registry')->get();
    // Create array of Twig templates.
    $templates = drupal_find_theme_templates($cache, $extension, drupal_get_path('theme', 'test_theme'));
    $templates += drupal_find_theme_templates($cache, $extension, drupal_get_path('module', 'node'));

    // Create a node and test different features of the debug markup.
    $node = $this->drupalCreateNode();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node);
    $output = $this->renderer->renderRoot($build);
    $this->assertStringContainsString('<!-- THEME DEBUG -->', $output, 'Twig debug markup found in theme output when debug is enabled.');
    $this->assertStringContainsString("THEME HOOK: 'node'", $output, 'Theme call information found.');
    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * ' . $xss_suggestion . PHP_EOL
      . '   * node--1--full' . $extension . PHP_EOL
      . '   x node--1' . $extension . PHP_EOL
      . '   * node--page--full' . $extension . PHP_EOL
      . '   * node--page' . $extension . PHP_EOL
      . '   * node--full' . $extension . PHP_EOL
      . '   * node' . $extension . PHP_EOL
      . '-->';
    $this->assertStringContainsString($expected, $output, 'Suggested template files found in order and node ID specific template shown as current template.');
    $template_filename = $templates['node__1']['path'] . '/' . $templates['node__1']['template'] . $extension;
    $this->assertStringContainsString("BEGIN OUTPUT from '$template_filename'", $output, 'Full path to current template file found.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node2 = $this->drupalCreateNode();
    $build = $builder->view($node2);
    $output = $this->renderer->renderRoot($build);
    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * ' . $xss_suggestion . PHP_EOL
      . '   * node--2--full' . $extension . PHP_EOL
      . '   * node--2' . $extension . PHP_EOL
      . '   * node--page--full' . $extension . PHP_EOL
      . '   * node--page' . $extension . PHP_EOL
      . '   * node--full' . $extension . PHP_EOL
      . '   x node' . $extension . PHP_EOL
      . '-->';
    $this->assertStringContainsString($expected, $output, 'Suggested template files found in order and base template shown as current template.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node3 = $this->drupalCreateNode();
    $build = ['#theme' => 'node__foo__bar'];
    $build += $builder->view($node3);
    $output = $this->renderer->renderRoot($build);
    $this->assertStringContainsString("THEME HOOK: 'node__foo__bar'", $output, 'Theme call information found.');
    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * ' . $xss_suggestion . PHP_EOL
      . '   * node--3--full' . $extension . PHP_EOL
      . '   * node--3' . $extension . PHP_EOL
      . '   * node--page--full' . $extension . PHP_EOL
      . '   * node--page' . $extension . PHP_EOL
      . '   * node--full' . $extension . PHP_EOL
      . '   * node--foo--bar' . $extension . PHP_EOL
      . '   * node--foo' . $extension . PHP_EOL
      . '   x node' . $extension . PHP_EOL
      . '-->';
    $this->assertStringContainsString($expected, $output, 'Suggested template files found in order and base template shown as current template.');

    // Disable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    $build = $builder->view($node);
    $output = $this->renderer->renderRoot($build);
    $this->assertStringNotContainsString('<!-- THEME DEBUG -->', $output, 'Twig debug markup not found in theme output when debug is disabled.');
  }

  /**
   * Tests debug markup for array suggestions and hook_theme_suggestions_HOOK().
   */
  public function testArraySuggestionsTwigDebugMarkup() {
    \Drupal::service('module_installer')->install(['theme_suggestions_test']);
    $extension = twig_extension();
    $this->drupalGet('theme-test/array-suggestions');
    $output = $this->getSession()->getPage()->getContent();

    $expected = "THEME HOOK: 'theme_test_array_suggestions__implemented'";
    $this->assertStringContainsString($expected, $output, 'Theme call information found.');

    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * theme-test-array-suggestions--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
      . '   * theme-test-array-suggestions--implemented--not-implemented' . $extension . PHP_EOL
      . '   x theme-test-array-suggestions--implemented' . $extension . PHP_EOL
      . '   * theme-test-array-suggestions--not-implemented' . $extension . PHP_EOL
      . '   * theme-test-array-suggestions--from-hook-theme-suggestions-hook' . $extension . PHP_EOL
      . '   * theme-test-array-suggestions' . $extension . PHP_EOL
      . '-->';
    $message = 'Suggested template files found in order and correct suggestion shown as current template.';
    $this->assertStringContainsString($expected, $output, $message);
  }

  /**
   * Tests debug markup for specific suggestions without implementation.
   */
  public function testUnimplementedSpecificSuggestionsTwigDebugMarkup() {
    $extension = twig_extension();
    $this->drupalGet('theme-test/specific-suggestion-not-found');
    $output = $this->getSession()->getPage()->getContent();

    $expected = "THEME HOOK: 'theme_test_specific_suggestions__variant_not_found__too'";
    $this->assertStringContainsString($expected, $output, 'Theme call information found.');

    $message = 'Suggested template files found in order and base template shown as current template.';
    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   * theme-test-specific-suggestions--variant-not-found--too' . $extension . PHP_EOL
      . '   * theme-test-specific-suggestions--variant-not-found' . $extension . PHP_EOL
      . '   x theme-test-specific-suggestions' . $extension . PHP_EOL
      . '-->';
    $this->assertStringContainsString($expected, $output, $message);
  }

  /**
   * Tests debug markup for specific suggestions.
   */
  public function testSpecificSuggestionsTwigDebugMarkup() {
    $extension = twig_extension();
    $this->drupalGet('theme-test/specific-suggestion-alter');
    $output = $this->getSession()->getPage()->getContent();

    $expected = "THEME HOOK: 'theme_test_specific_suggestions__variant'";
    $this->assertStringContainsString($expected, $output, 'Theme call information found.');

    $expected = '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
      . '   x theme-test-specific-suggestions--variant' . $extension . PHP_EOL
      . '   * theme-test-specific-suggestions' . $extension . PHP_EOL
      . '-->';
    $message = 'Suggested template files found in order and suggested template shown as current.';
    $this->assertStringContainsString($expected, $output, $message);
  }

}
