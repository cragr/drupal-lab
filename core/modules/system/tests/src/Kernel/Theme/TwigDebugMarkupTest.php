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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['theme_test', 'node', 'system', 'user'];

  /**
   * The twig config to apply during container rebuils.
   * @var array
   */
  protected $twigConfig = [
    'debug' => FALSE,
    'auto_reload' => NULL,
    'cache' => TRUE,
  ];

  protected function setUp() {
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

    $container->setParameter('twig.config',  $this->twigConfig);
  }

  /**
   * Tests debug markup added to Twig template output.
   */
  public function testTwigDebugMarkup() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $extension = '.html.twig';
    \Drupal::service('theme_handler')->install(['test_theme']);
    $this->config('system.theme')->set('default', 'test_theme')->save();

    NodeType::create([
      'type' => 'page',
    ])->save();
    $user = User::create([
      'name' => 'test user',
    ]);
    $user->save();

    // Enable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $this->twigConfig = $parameters;

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
    $build = node_view($node);
    $output = $renderer->renderRoot($build);
    $this->assertTrue(strpos($output, '<!-- THEME DEBUG -->') !== FALSE, 'Twig debug markup found in theme output when debug is enabled.');
    $this->assertTrue(strpos($output, "THEME HOOK: 'node'") !== FALSE, 'Theme call information found.');
    $this->assertContains(
<<<'TWIG'
* node--1--full.html.twig
x node--1.html.twig
* node--page--full.html.twig
* node--page.html.twig
* node--full.html.twig
* node--{<script type="text/javascript">alert('yo');</script>.html.twig}
* node.html.twig
TWIG
, (string) $output, 'Suggested template files found in order and node ID specific template shown as current template.');

    $template_filename = $templates['node__1']['path'] . '/' . $templates['node__1']['template'] . $extension;
    $this->assertTrue(strpos($output, "BEGIN OUTPUT from '$template_filename'") !== FALSE, 'Full path to current template file found.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node2 = Node::create([
      'type' => 'page',
      'uid' => $user,
      'title' => 'test2',
    ]);
    $node2->save();
    $build = node_view($node2);
    $output = $renderer->renderRoot($build);
    $this->assertTrue(strpos($output, '* node--2--full' . $extension . PHP_EOL . '   * node--2' . $extension . PHP_EOL . '   * node--page--full' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node--full' . $extension . PHP_EOL . '   x node' . $extension) !== FALSE, 'Suggested template files found in order and base template shown as current template.');

    // Create another node and make sure the template suggestions shown in the
    // debug markup are correct.
    $node3 = Node::create([
      'type' => 'page',
      'uid' => $user,
      'title' => 'test2',
    ]);
    $node3->save();
    $build = ['#theme' => 'node__foo__bar'];
    $build += node_view($node3);
    $output = $renderer->renderRoot($build);
    $this->assertTrue(strpos($output, "THEME HOOK: 'node__foo__bar'") !== FALSE, 'Theme call information found.');
    $this->assertTrue(strpos($output, '* node--foo--bar' . $extension . PHP_EOL . '   * node--foo' . $extension . PHP_EOL . '   * node--&lt;script type=&quot;text/javascript&quot;&gt;alert(&#039;yo&#039;);&lt;/script&gt;' . $extension . PHP_EOL . '   * node--3--full' . $extension . PHP_EOL . '   * node--3' . $extension . PHP_EOL . '   * node--page--full' . $extension . PHP_EOL . '   * node--page' . $extension . PHP_EOL . '   * node--full' . $extension . PHP_EOL . '   x node' . $extension) !== FALSE, 'Suggested template files found in order and base template shown as current template.');

    // Disable debug, rebuild the service container, and clear all caches.
    $parameters = $this->container->getParameter('twig.config');
    $parameters['debug'] = FALSE;
    $this->setContainerParameter('twig.config', $parameters);
    $this->rebuildContainer();
    $this->resetAll();

    $build = node_view($node);
    $output = $renderer->renderRoot($build);
    $this->assertFalse(strpos($output, '<!-- THEME DEBUG -->') !== FALSE, 'Twig debug markup not found in theme output when debug is disabled.');
  }

}
