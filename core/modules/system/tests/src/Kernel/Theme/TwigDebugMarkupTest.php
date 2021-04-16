<?php

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernelInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Tests for Twig debug markup.
 *
 * @group Theme
 */
class TwigDebugMarkupTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['theme_test', 'node', 'system', 'user'];

  /**
   * The Twig config to apply during container rebuilds.
   *
   * @var array
   */
  protected $twigConfig = [
    'debug' => FALSE,
    'auto_reload' => NULL,
    'cache' => TRUE,
  ];

  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('system');
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $container->setParameter('twig.config', $this->twigConfig);
  }

  /**
   * Tests debug markup added to Twig template output.
   */
  public function testTwigDebugMarkup() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $extension = '.html.twig';
    \Drupal::service('theme_installer')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();

    NodeType::create([
      'type' => 'page',
    ])->save();
    $user = User::create([
      'name' => 'test user',
    ]);
    $user->save();

    // Enable debug, rebuild the service container, and clear all caches.
    $this->twigConfig['debug'] = TRUE;
    $kernel = $this->container->get('kernel');
    assert($kernel instanceof DrupalKernelInterface);
    $kernel->rebuildContainer();
    $this->container = $kernel->getContainer();

    $cache = $this->container->get('theme.registry')->get();
    // Create array of Twig templates.
    $templates = drupal_find_theme_templates($cache, $extension, drupal_get_path('theme', 'test_theme'));
    $templates += drupal_find_theme_templates($cache, $extension, drupal_get_path('module', 'node'));

    // Create a node and test different features of the debug markup.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test node',
      'uid' => $user,
    ]);
    $node->save();
    $builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $build = $builder->view($node);
    $output = $renderer->renderRoot($build);
    $this->assertStringContainsString('<!-- THEME DEBUG -->', $output, 'Twig debug markup found in theme output when debug is enabled.');
    $this->assertStringContainsString("THEME HOOK: 'node'", $output, 'Theme call information found.');
    $escaped = Html::escape('node--<script type="text/javascript">alert(\'yo\');</script>');
    $expected = <<<TWIG
   * node--1--full$extension
   x node--1$extension
   * node--page--full$extension
   * node--page$extension
   * node--full$extension
   * $escaped$extension
   * node$extension
TWIG;
    $this->assertStringContainsString($expected, $output, 'Suggested template files found in order and node ID specific template shown as current template.');
    $template_filename = $templates['node__1']['path'] . '/' . $templates['node__1']['template'] . $extension;
    $this->assertStringContainsString("BEGIN OUTPUT from '$template_filename'", $output, 'Full path to current template file found.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node2 = Node::create([
      'type' => 'page',
      'uid' => $user,
      'title' => 'test2',
    ]);
    $node2->save();
    $build = $builder->view($node2);
    $output = $renderer->renderRoot($build);
    $expected = <<<TWIG
   * node--2--full$extension
   * node--2$extension
   * node--page--full$extension
   * node--page$extension
   * node--full$extension
   * $escaped$extension
   x node$extension
TWIG;
    $this->assertStringContainsString($expected, $output, 'Suggested template files found in order and base template shown as current template.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node3 = Node::create([
      'type' => 'page',
      'uid' => $user,
      'title' => 'test2',
    ]);
    $node3->save();
    $build = ['#theme' => 'node__foo__bar'];
    $build += $builder->view($node3);
    $output = $renderer->renderRoot($build);
    $this->assertStringContainsString("THEME HOOK: 'node__foo__bar'", $output, 'Theme call information found.');
    $expected = <<<TWIG
   * node--foo--bar$extension
   * node--foo$extension
   * node--3--full$extension
   * node--3$extension
   * node--page--full$extension
   * node--page$extension
   * node--full$extension
   * $escaped$extension
   x node$extension
TWIG;
    $this->assertStringContainsString($expected, $output, 'Suggested template files found in order and base template shown as current template.');

    // Disable debug, rebuild the service container, and clear all caches.
    $this->twigConfig['debug'] = FALSE;
    $kernel = $this->container->get('kernel');
    assert($kernel instanceof DrupalKernelInterface);
    $kernel->rebuildContainer();
    $this->container = $kernel->getContainer();

    $build = $builder->view($node);
    $output = $renderer->renderRoot($build);
    $this->assertStringNotContainsString('<!-- THEME DEBUG -->', $output, 'Twig debug markup not found in theme output when debug is disabled.');
  }

}
