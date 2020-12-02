<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests theme suggestions alter hooks.
 *
 * @group Theme
 */
class ThemeSuggestionsAlterTest extends BrowserTestBase {

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
   * Helper function to test render arrays with modules and themes.
   *
   * @param array $build
   *   A render array.
   * @param string[] $modules
   *   An array of modules to install before the test.
   * @param string $theme
   *   The theme to set as the default theme.
   * @param string[] $expected
   *   The string(s) expected in the rendered output.
   * @param string[] $unexpected
   *   The string(s) expected to NOT occur in the rendered output.
   */
  public function runTemplateSuggestionTest(array $build, array $modules, string $theme, array $expected, array $unexpected = []) {
    // Enable modules.
    if (!empty($modules)) {
      $this->container->get('module_installer')->install($modules);
    }
    // Set the default theme.
    if ($theme) {
      $this->container->get('theme_installer')->install([$theme]);
      $this->config('system.theme')
        ->set('default', $theme)
        ->save();
    }
    // Clear caches.
    $this->resetAll();

    // Render a template.
    $output = $this->container->get('renderer')->renderRoot($build);

    // Check the output for expected results.
    foreach ($expected as $expected_string) {
      $this->assertStringContainsString($expected_string, $output, $this->getName());
    }

    // Check the output for unexpected results.
    foreach ($unexpected as $unexpected_string) {
      $this->assertStringNotContainsString($unexpected_string, $output, $this->getName());
    }
  }

  /**
   * Tests hook_theme_suggestions_HOOK.
   *
   * Note: themes cannot use this hook.
   *
   * @param string[] $modules
   *   An array of modules to install before the test.
   * @param string $theme
   *   The theme to set as the default theme.
   * @param string[] $expected
   *   The string(s) expected in the rendered output.
   *
   * @dataProvider providerHookThemeSuggestionsHook
   */
  public function testHookThemeSuggestionsHook(array $modules, string $theme, array $expected) {
    $this->runTemplateSuggestionTest(
      [
        '#theme' => 'theme_test_suggestion_provided',
      ],
      $modules,
      $theme,
      $expected
    );
  }

  /**
   * Data provider for testHookThemeSuggestionsHook().
   *
   * @see testHookThemeSuggestionsHook()
   */
  public function providerHookThemeSuggestionsHook() {
    return [
      'Base template used when suggestion template not found' => [
        'modules' => [],
        'theme' => '',
        'expected' => [
          'Template for testing suggestions provided by the module declaring the theme hook.',
        ],
      ],
      'Suggestion template used when suggestion template is found' => [
        'modules' => [],
        // The test_theme contains a template suggested by theme_test.module in
        // theme_test_theme_suggestions_theme_test_suggestion_provided().
        'theme' => 'test_theme',
        'expected' => [
          'Template overridden based on suggestion provided by the module declaring the theme hook.',
        ],
      ],
    ];
  }

  /**
   * Tests hook_theme_suggestions_alter().
   *
   * @param string[] $modules
   *   An array of modules to install before the test.
   * @param string $theme
   *   The theme to set as the default theme.
   * @param string[] $expected
   *   The string(s) expected in the rendered output.
   *
   * @dataProvider providerHookThemeSuggestionsAlter
   */
  public function testHookThemeSuggestionsAlter(array $modules, string $theme, array $expected) {
    $this->runTemplateSuggestionTest(
      [
        '#theme' => 'theme_test_general_suggestions',
      ],
      $modules,
      $theme,
      $expected
    );
  }

  /**
   * Data provider for testHookThemeSuggestionsAlter().
   *
   * @see testHookThemeSuggestionsAlter()
   */
  public function providerHookThemeSuggestionsAlter() {
    $extension = '.html.twig';
    return [
      'Base template used when suggestion template is not available' => [
        'modules' => [],
        'theme' => '',
        'expected' => [
          'Original template for testing hook_theme_suggestions_alter().',
          "<!-- BEGIN OUTPUT from 'core/modules/system/tests/modules/theme_test/templates/theme-test-general-suggestions$extension' -->",
        ],
      ],
      'Suggestion provided by a module\'s hook_theme_suggestions_alter is used' => [
        // @see theme_suggestions_test_theme_suggestions_alter()
        'modules' => ['theme_suggestions_test'],
        'theme' => '',
        'expected' => [
          'Template overridden based on new theme suggestion provided by a module via hook_theme_suggestions_alter().',
          "<!-- BEGIN OUTPUT from 'core/modules/system/tests/modules/theme_suggestions_test/templates/theme-test-general-suggestions--module-override$extension' -->",
        ],
      ],
      'Suggestion provided by a theme\'s hook_theme_suggestions_alter is used' => [
        'modules' => [],
        // @see test_theme_theme_suggestions_alter()
        'theme' => 'test_theme',
        'expected' => [
          'Template overridden based on new theme suggestion provided by the test_theme theme via hook_theme_suggestions_alter().',
          "<!-- BEGIN OUTPUT from 'core/modules/system/tests/themes/test_theme/templates/theme-test-general-suggestions--theme-override$extension' -->",
        ],
      ],
      'Themes implementing hook_theme_suggestions_alter override modules' => [
        'modules' => ['theme_suggestions_test'],
        'theme' => 'test_theme',
        'expected' => [
          'Template overridden based on new theme suggestion provided by the test_theme theme via hook_theme_suggestions_alter().',
          "<!-- BEGIN OUTPUT from 'core/modules/system/tests/themes/test_theme/templates/theme-test-general-suggestions--theme-override$extension' -->",
        ],
      ],
    ];
  }

  /**
   * Tests hook_theme_suggestions_HOOK_alter().
   *
   * @param string[] $modules
   *   An array of modules to install before the test.
   * @param string $theme
   *   The theme to set as the default theme.
   * @param string[] $expected
   *   The string(s) expected in the rendered output.
   *
   * @dataProvider providerHookThemeSuggestionsHookAlter
   */
  public function testHookThemeSuggestionsHookAlter(array $modules, string $theme, array $expected) {
    $this->runTemplateSuggestionTest(
      [
        '#theme' => 'theme_test_suggestions',
      ],
      $modules,
      $theme,
      $expected
    );
  }

  /**
   * Data provider for testHookThemeSuggestionsHookAlter().
   *
   * @see testHookThemeSuggestionsHookAlter()
   */
  public function providerHookThemeSuggestionsHookAlter() {
    return [
      'Base template used when suggestion template is not available' => [
        'modules' => [],
        'theme' => '',
        'expected' => [
          'Original template for testing hook_theme_suggestions_HOOK_alter().',
        ],
      ],
      'Suggestion provided by a module\'s hook_theme_suggestions_HOOK_alter is used' => [
        // @see theme_suggestions_test_theme_suggestions_theme_test_suggestions_alter()
        'modules' => ['theme_suggestions_test'],
        'theme' => '',
        'expected' => [
          'Template overridden based on new theme suggestion provided by a module via hook_theme_suggestions_HOOK_alter().',
        ],
      ],
      'Suggestion provided by a theme\'s hook_theme_suggestions_HOOK_alter is used' => [
        'modules' => [],
        // @see test_theme_theme_suggestions_theme_test_suggestions_alter()
        'theme' => 'test_theme',
        'expected' => [
          'Template overridden based on new theme suggestion provided by the test_theme theme via hook_theme_suggestions_HOOK_alter().',
        ],
      ],
    ];
  }

  /**
   * Tests non-"base hook" suggestions with hook_theme_suggestions_HOOK_alter().
   *
   * @param string[] $modules
   *   An array of modules to install before the test.
   * @param string $theme
   *   The theme to set as the default theme.
   * @param string[] $expected
   *   The string(s) expected in the rendered output.
   *
   * @dataProvider providerNonBaseHookThemeSuggestions
   */
  public function testNonBaseHookThemeSuggestions(array $modules, string $theme, array $expected) {
    $this->runTemplateSuggestionTest(
      [
        '#theme' => 'theme_test_specific_suggestions__variant',
      ],
      $modules,
      $theme,
      $expected
    );
  }

  /**
   * Data provider for testNonBaseHookThemeSuggestions().
   *
   * @see testNonBaseHookThemeSuggestions()
   */
  public function providerNonBaseHookThemeSuggestions() {
    $extension = '.html.twig';
    return [
      'Base template used when suggestion template is not available' => [
        'modules' => [],
        'theme' => '',
        'expected' => [
          'Template for testing specific theme calls.',
        ],
      ],
      'Suggestion provided by a module\'s hook_theme_suggestions_HOOK_alter is used' => [
        // @see theme_suggestions_test_theme_suggestions_theme_test_specific_suggestions_alter()
        'modules' => ['theme_suggestions_test'],
        'theme' => 'test_theme',
        'expected' => [
          'Template overridden based on suggestion alter hook determined by a module\'s hook_theme_suggestions_HOOK_alter().',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   x theme-test-specific-suggestions--variant--foo' . $extension . PHP_EOL
          . '   * theme-test-specific-suggestions--variant' . $extension . PHP_EOL
          . '   * theme-test-specific-suggestions' . $extension . PHP_EOL
          . '-->' . PHP_EOL,
        ],
      ],
      'Suggestion template provided by a theme' => [
        'modules' => [],
        'theme' => 'test_theme',
        'expected' => [
          'Template overridden based on suggestion alter hook determined by the base hook.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   x theme-test-specific-suggestions--variant' . $extension . PHP_EOL
          . '   * theme-test-specific-suggestions' . $extension . PHP_EOL
          . '-->' . PHP_EOL,
        ],
      ],
    ];
  }

  /**
   * Tests debug markup for non-"base hook" suggestions without implementation.
   */
  public function testUnimplementedNonBaseHookThemeSuggestions() {
    $extension = '.html.twig';
    $this->runTemplateSuggestionTest(
      [
        '#theme' => 'theme_test_specific_suggestions__variant_not_found__too',
      ],
      [],
      '',
      [
        "THEME HOOK: 'theme_test_specific_suggestions__variant_not_found__too'",
        '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
        . '   * theme-test-specific-suggestions--variant-not-found--too' . $extension . PHP_EOL
        . '   * theme-test-specific-suggestions--variant-not-found' . $extension . PHP_EOL
        . '   x theme-test-specific-suggestions' . $extension . PHP_EOL
        . '-->',
      ]
    );
  }

  /**
   * Tests an array of suggestions with all alter hooks.
   *
   * @param string[] $modules
   *   An array of modules to install before the test.
   * @param string $theme
   *   The theme to set as the default theme.
   * @param string[] $expected
   *   The string(s) expected in the rendered output.
   * @param string[] $unexpected
   *   The string(s) expected to NOT occur in the rendered output.
   *
   * @dataProvider providerThemeSuggestionsOrdering
   */
  public function testThemeSuggestionsOrdering(array $modules, string $theme, array $expected, array $unexpected) {
    $this->runTemplateSuggestionTest(
      [
        '#theme' => 'theme_test_base1__from_theme_property__too',
      ],
      $modules,
      $theme,
      $expected,
      $unexpected
    );
  }

  /**
   * Data provider for testThemeSuggestionsOrdering().
   *
   * @see testThemeSuggestionsOrdering()
   */
  public function providerThemeSuggestionsOrdering() {
    $extension = '.html.twig';
    return [
      '#theme property suggestions always override ones from hook_theme_suggestions_hook' => [
        'modules' => ['theme_suggestions_base1_test'],
        'theme' => '',
        'expected' => [
          'Template for testing suggestion hooks when #theme contains a list of theme suggestions.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-alter--but-reordered' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property--too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook' . $extension . PHP_EOL
          . '   x theme-test-base1' . $extension . PHP_EOL
          . '-->' . PHP_EOL,
        ],
        'unexpected' => [],
      ],
      'adding a new template implementation does not change order of suggestions' => [
        'modules' => ['theme_suggestions_base1_test'],
        'theme' => 'test_theme',
        'expected' => [
          'This theme_test_base1__from_theme_property__too template is implemented.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-alter--but-reordered' . $extension . PHP_EOL
          . '   x theme-test-base1--from-theme-property--too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook' . $extension . PHP_EOL
          . '   * theme-test-base1' . $extension . PHP_EOL
          . '-->' . PHP_EOL,
        ],
        'unexpected' => [],
      ],
    ];
  }

  /**
   * Tests an array of suggestions with all alter hooks.
   *
   * @param string[] $modules
   *   An array of modules to install before the test.
   * @param string $theme
   *   The theme to set as the default theme.
   * @param string[] $expected
   *   The string(s) expected in the rendered output.
   * @param string[] $unexpected
   *   The string(s) expected to NOT occur in the rendered output.
   *
   * @dataProvider providerArrayThemeSuggestions
   */
  public function testArrayThemeSuggestions(array $modules, string $theme, array $expected, array $unexpected) {
    $this->runTemplateSuggestionTest(
      [
        '#theme' => [
          'theme_test_base5',
          'theme_test_base4',
          'theme_test_base3__from_theme_property',
          'theme_test_base3',
          'theme_test_base2__variant_not_implemented',
          'theme_test_base1__from_theme_property__too',
        ],
      ],
      $modules,
      $theme,
      $expected,
      $unexpected
    );
  }

  /**
   * Data provider for testArrayThemeSuggestions().
   *
   * @see testArrayThemeSuggestions()
   */
  public function providerArrayThemeSuggestions() {
    $extension = '.html.twig';
    return [
      'Only the last #theme array entry is expanded into suggestions' => [
        'modules' => [],
        'theme' => '',
        'expected' => [
          'Template for testing suggestion hooks when #theme contains a list of theme suggestions.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base5' . $extension . PHP_EOL
          . '   * theme-test-base4' . $extension . PHP_EOL
          . '   * theme-test-base3--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base3' . $extension . PHP_EOL
          . '   * theme-test-base2--variant-not-implemented' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property--too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property' . $extension . PHP_EOL
          . '   x theme-test-base1' . $extension . PHP_EOL
          . '-->' . PHP_EOL,
        ],
        'unexpected' => [
          'This theme_test_base2 template is implemented, but never used.',
          'x theme-test-base2' . $extension . PHP_EOL,
          '* theme-test-base2' . $extension . PHP_EOL,
        ],
      ],
      'Confirm unexpanded theme_test_base2 suggestion would be used if expanded' => [
        'modules' => ['theme_suggestions_base2_test'],
        'theme' => '',
        'expected' => [
          'This theme_test_base2 template is implemented, but never used.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base5' . $extension . PHP_EOL
          . '   * theme-test-base4' . $extension . PHP_EOL
          . '   * theme-test-base3--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base3' . $extension . PHP_EOL
          . '   * theme-test-base2--variant-not-implemented' . $extension . PHP_EOL
          . '   x theme-test-base2' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property--too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base1' . $extension . PHP_EOL
          . '-->' . PHP_EOL,
        ],
        'unexpected' => [],
      ],
      '#theme property suggestions always override ones from hook_theme_suggestions_hook' => [
        'modules' => ['theme_suggestions_base1_test'],
        'theme' => '',
        'expected' => [
          'Template for testing suggestion hooks when #theme contains a list of theme suggestions.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base5' . $extension . PHP_EOL
          . '   * theme-test-base4' . $extension . PHP_EOL
          . '   * theme-test-base3--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base3' . $extension . PHP_EOL
          . '   * theme-test-base2--variant-not-implemented' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-alter--but-reordered' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property--too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook' . $extension . PHP_EOL
          . '   x theme-test-base1' . $extension . PHP_EOL
          . '-->' . PHP_EOL,
        ],
        'unexpected' => [],
      ],
      'adding a new template implementation does not change order of suggestions' => [
        'modules' => ['theme_suggestions_base1_test'],
        'theme' => 'test_theme',
        'expected' => [
          'This theme_test_base1__from_theme_property__too template is implemented.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base5' . $extension . PHP_EOL
          . '   * theme-test-base4' . $extension . PHP_EOL
          . '   * theme-test-base3--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base3' . $extension . PHP_EOL
          . '   * theme-test-base2--variant-not-implemented' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook-alter--but-reordered' . $extension . PHP_EOL
          . '   x theme-test-base1--from-theme-property--too' . $extension . PHP_EOL
          . '   * theme-test-base1--from-theme-property' . $extension . PHP_EOL
          . '   * theme-test-base1--from-hook-theme-suggestions-hook' . $extension . PHP_EOL
          . '   * theme-test-base1' . $extension . PHP_EOL
          . '-->' . PHP_EOL,
        ],
        'unexpected' => [],
      ],
      'ordering is correct when no suggestions from hook_theme_suggestions_HOOK_alter' => [
        'modules' => ['theme_suggestions_base3_test'],
        'theme' => '',
        'expected' => [
          'This theme_test_base3 template is implemented.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base5' . $extension . PHP_EOL
          . '   * theme-test-base4' . $extension . PHP_EOL
          . '   * theme-test-base3--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
          . '   * theme-test-base3--from-theme-property' . $extension . PHP_EOL
          . '   x theme-test-base3' . $extension . PHP_EOL,
        ],
        'unexpected' => [],
      ],
      'different "base hook" specified in theme registry' => [
        'modules' => ['theme_suggestions_base4_test'],
        'theme' => '',
        'expected' => [
          'This theme_test_base4 template is implemented and has a different "base hook".',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base5' . $extension . PHP_EOL
          . '   * theme-suggestions-base4-test-alternate--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
          . '   x theme-test-base4' . $extension . PHP_EOL
          . '   * theme-suggestions-base4-test-alternate--from-hook-theme-suggestions-hook' . $extension . PHP_EOL
          . '   * theme-suggestions-base4-test-alternate' . $extension . PHP_EOL
          . '   * theme-test-base3--from-theme-property' . $extension . PHP_EOL,
        ],
        'unexpected' => [],
      ],
      'implementation of base hook template not used when different "base hook" specified in theme registry' => [
        'modules' => ['theme_suggestions_base4_test'],
        'theme' => 'test_theme',
        'expected' => [
          'This theme_test_base4 template is implemented and has a different "base hook".',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base5' . $extension . PHP_EOL
          . '   * theme-suggestions-base4-test-alternate--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
          . '   x theme-test-base4' . $extension . PHP_EOL
          . '   * theme-suggestions-base4-test-alternate--from-hook-theme-suggestions-hook' . $extension . PHP_EOL
          . '   * theme-suggestions-base4-test-alternate' . $extension . PHP_EOL
          . '   * theme-test-base3--from-theme-property' . $extension . PHP_EOL,
        ],
        'unexpected' => [
          'This theme_suggestions_base4_test_alternate template is implemented, but not used.',
        ],
      ],
      'implementation of base hook suggestion used when different "base hook" specified in theme registry' => [
        'modules' => [
          'theme_suggestions_base1_test',
          'theme_suggestions_base4_test',
        ],
        'theme' => 'test_theme',
        'expected' => [
          'This theme_suggestions_base4_test_alternate__from_hook_theme_suggestions_hook_alter template is implemented.',
          '<!-- FILE NAME SUGGESTIONS:' . PHP_EOL
          . '   * theme-test-base5' . $extension . PHP_EOL
          . '   x theme-suggestions-base4-test-alternate--from-hook-theme-suggestions-hook-alter' . $extension . PHP_EOL
          . '   * theme-test-base4' . $extension . PHP_EOL
          . '   * theme-suggestions-base4-test-alternate--from-hook-theme-suggestions-hook' . $extension . PHP_EOL
          . '   * theme-suggestions-base4-test-alternate' . $extension . PHP_EOL
          . '   * theme-test-base3--from-theme-property' . $extension . PHP_EOL,
        ],
        'unexpected' => [],
      ],
    ];
  }

  /**
   * Tests execution order of theme suggestion hooks.
   */
  public function testExecutionOrder() {
    $this->runTemplateSuggestionTest(
      [
        '#theme' => 'theme_test_suggestions',
      ],
      ['theme_suggestions_test'],
      'test_theme',
      [
        'Template overridden based on new theme suggestion provided by the test_theme theme via hook_theme_suggestions_HOOK_alter().',
      ]
    );

    // Retrieve all messages we've set via \Drupal::messenger()->addStatus().
    $messages = \Drupal::messenger()->messagesByType('status');

    // Ensure that the order is:
    // 1. hook_theme_suggestions_HOOK()
    // 2. Grouped by module:
    //    hook_theme_suggestions_alter()
    //    hook_theme_suggestions_HOOK_alter()
    // 3. Grouped by theme:
    //    hook_theme_suggestions_alter()
    //    hook_theme_suggestions_HOOK_alter()
    $expected_order = [
      'theme_test_theme_suggestions_theme_test_suggestions() executed.',
      'theme_suggestions_test_theme_suggestions_alter() executed.',
      'theme_suggestions_test_theme_suggestions_theme_test_suggestions_alter() executed.',
      'theme_test_theme_suggestions_alter() executed.',
      'theme_test_theme_suggestions_theme_test_suggestions_alter() executed.',
      'test_theme_theme_suggestions_alter() executed.',
      'test_theme_theme_suggestions_theme_test_suggestions_alter() executed.',
    ];
    $this->assertEquals($expected_order, $messages);
  }

}
