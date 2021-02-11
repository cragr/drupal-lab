<?php

namespace Drupal\Tests\tour\FunctionalJavascript;

use Drupal\Core\Config\FileStorage;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\tour\Entity\Tour;

/**
 * Tests Tour's backwards compatible markup.
 *
 * @group tour
 * @group legacy
 */
class TourLegacyMarkupTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'tour',
    'tour_legacy_test',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $config_path = drupal_get_path('module', 'tour') . '/tests/fixtures/legacy_config';
    $source = new FileStorage($config_path);
    $config_storage = \Drupal::service('config.storage');
    $this->assertTrue($source->exists('tour.tour.tour-test-legacy'));
    $config_storage->write('tour.tour.tour-test-legacy', $source->read('tour.tour.tour-test-legacy'));
    drupal_flush_all_caches();
    //    $leg = $this->container->get('config.factory')->getEditable('tour.tour.tour-test-legacy');
//    $this->assertEquals(1, 2, print_r($leg, 1));

    $admin_user = $this->drupalCreateUser([
      'access toolbar',
      'access tour',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Confirms backwards compatible markup.
   *
   * @param string $path
   *   The path to check.
   * @param string $theme
   *   The theme used by the tests.
   *
   * @dataProvider providerTestTourTipMarkup
   */
  public function testTourTipMarkup($path, $theme = 'stable') {
    // Install the specified theme and make it default if that is not already
    // the case.
    if ($theme !== $this->defaultTheme) {
      $theme_manager = $this->container->get('theme.manager');
      $this->container->get('theme_installer')->install(['stable9'], TRUE);

      $system_theme_config = $this->container->get('config.factory')->getEditable('system.theme');
      $system_theme_config
        ->set('default', 'stable9')
        ->save();
      $this->rebuildAll();
      $this->assertSame('stable9', $theme_manager->getActiveTheme()->getName());
    }

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet($path);
    // BAD
    $routes = [];
    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();
    $results = \Drupal::entityQuery('tour')
      //      ->condition('routes.*.route_name', 'tour_test.legacy')
      ->execute();
    if (!empty($results) && $tours = Tour::loadMultiple(array_keys($results))) {
      foreach ($tours as $id => $tour) {
        $routes[] = $tour->getRoutes();
      }
    }
//    $this->assertEquals(1, 2, "reult:: " . count($tours) .  ' Route name:' . $route_name . ' Routes: '. print_r($routes, 1));
//    $assert_session->waitForElementVisible('css', '.test-go', 500000000);
    // BAD
    $assert_session->waitForElementVisible('css', '#toolbar-tab-tour button');
    $page->find('css', '#toolbar-tab-tour button')->press();
//    $this->assertEquals(1, 2, "reult:: " . count($tours) .  ' Route name:' . $route_name . ' Routes: '. print_r($routes, 1));

    $this->assertToolTipMarkup(0, 'top');
    $page->find('css', '.joyride-tip-guide[data-index="0"]')->clickLink('Next');
    $this->assertToolTipMarkup(1, '', 'image');
    $page->find('css', '.joyride-tip-guide[data-index="1"]')->clickLink('Next');
    $this->assertToolTipMarkup(2, 'top', 'body');
    $tip_content = $assert_session->waitForElementVisible('css', '.joyride-tip-guide[data-index="2"] .joyride-content-wrapper');

    $additional_paragraph = $tip_content->find('css', '.tour-tip-body + p');
    $this->assertNotNull($additional_paragraph, 'Tip 3 has an additional paragraph that is a sibling to the main paragraph.');
    $additional_list = $tip_content->find('css', '.tour-tip-body + p + ul');
    $this->assertNotNull($additional_list, 'Tip 3 has an additional unordered list that is a sibling to the main paragraph.');
  }

  /**
   * Asserts the markup structure of a tip.
   *
   * @param int $index
   *   The position of the tip within the tour.
   * @param string $nub_position
   *   The expected position of the nub arrow.
   * @param string $joyride_content_container_name
   *   For identifying classnames specific to a tip type.
   */
  private function assertToolTipMarkup($index, $nub_position, $joyride_content_container_name = 'body') {
    $assert_session = $this->assertSession();
    $tip = $assert_session->waitForElementVisible('css', ".joyride-tip-guide[data-index=\"$index\"]");
    $this->assertNotNull($tip, 'The tour tip element is present.' . "index: $index");

    $nub = $tip->find('css', ".joyride-tip-guide[data-index=\"$index\"] > .joyride-nub");
    $this->assertNotNull($nub, 'The nub element is present.');
    if (!empty($nub_position)) {
      $this->assertTrue($nub->hasClass($nub_position), 'The nub has a class that indicates its configured position.');
    }

    $content_wrapper = $tip->find('css', '.joyride-nub + .joyride-content-wrapper');
    $this->assertNotNull($content_wrapper, 'The joyride content wrapper exists, and is the next sibling of the nub.');

    $label = $tip->find('css', '.joyride-content-wrapper > h2.tour-tip-label:first-child');
    $this->assertNotNull($label, 'The tour tip label is an h2, and is the first child of the content wrapper.');

    $tip_content = $content_wrapper->find('css', "h2.tour-tip-label + p.tour-tip-$joyride_content_container_name");
    $this->assertNotNull($tip_content, 'The tip\'s main paragraph is the next sibling of the label, and has a class based on the value of getJoyrideContentContainerName().');

    $tour_progress = $content_wrapper->find('css', "h2.tour-tip-label + p.tour-tip-$joyride_content_container_name ~ div.tour-progress");
    $this->assertNotNull($tour_progress, 'The div containing tour progress info is present, and is the next sibling of the main paragraph.');

    $next_item = $content_wrapper->find('css', ".tour-progress + a.joyride-next-tip.button.button--primary");
    $this->assertNotNull($next_item, 'The "Next" link is present, and the next sibling of the div containing progress info.');

    $close_tour = $content_wrapper->find('css', ".joyride-content-wrapper > a.joyride-close-tip:last-child");
    $this->assertNotNull($close_tour, 'The "Close" link is present, is an immediate child of the content wrapper, and is the last child.');
  }

  /**
   * Data Provider.
   *
   * @return \string[][]
   *   An array with two potential items:
   *   - The different path the test will run on.
   *   - The active theme when running the tests.
   */
  public function providerTestTourTipMarkup() {
    return [
      'Using the the deprecated TipPlugin with Stable theme' => ['tour-test-legacy'],
      'Using current TourTipPlugin with Stable theme' => ['tour-test-1'],
      'Using the the deprecated TipPlugin with Stable 9 theme' => ['tour-test-legacy', 'stable9'],
      'Using current TourTipPlugin with Stable 9 theme' => ['tour-test-1', 'stable9'],
    ];
  }

  /**
   * Test plugin and schema deprecations.
   */
  public function testTipDeprecations() {
    $this->expectDeprecation('Drupal\tour\TipPluginInterface is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use Drupal\tour\TourTipPluginInterface instead. See https://www.drupal.org/node/3195234');
    $this->expectDeprecation('Drupal\tour\TipPluginBase is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use Drupal\tour\TourTipPluginBase instead. See https://www.drupal.org/node/3195234');
    $this->expectDeprecation("The tour.tip 'attributes' config schema property is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead of 'data-class' and 'data-id' attributes, use 'selector' to specify the element a tip attaches to. See https://www.drupal.org/node/3195234");
    $this->drupalGet('tour-test-legacy');
  }

}
