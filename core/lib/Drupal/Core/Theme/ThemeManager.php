<?php

namespace Drupal\Core\Theme;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Template\Attribute;

/**
 * Provides the default implementation of a theme manager.
 */
class ThemeManager implements ThemeManagerInterface {

  /**
   * The theme negotiator.
   *
   * @var \Drupal\Core\Theme\ThemeNegotiatorInterface
   */
  protected $themeNegotiator;

  /**
   * The theme registry used to render an output.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * Contains the current active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $activeTheme;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a new ThemeManager object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Theme\ThemeNegotiatorInterface $theme_negotiator
   *   The theme negotiator.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($root, ThemeNegotiatorInterface $theme_negotiator, ThemeInitializationInterface $theme_initialization, ModuleHandlerInterface $module_handler) {
    $this->root = $root;
    $this->themeNegotiator = $theme_negotiator;
    $this->themeInitialization = $theme_initialization;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Sets the theme registry.
   *
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   *
   * @return $this
   */
  public function setThemeRegistry(Registry $theme_registry) {
    $this->themeRegistry = $theme_registry;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTheme(RouteMatchInterface $route_match = NULL) {
    if (!isset($this->activeTheme)) {
      $this->initTheme($route_match);
    }
    return $this->activeTheme;
  }

  /**
   * {@inheritdoc}
   */
  public function hasActiveTheme() {
    return isset($this->activeTheme);
  }

  /**
   * {@inheritdoc}
   */
  public function resetActiveTheme() {
    $this->activeTheme = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveTheme(ActiveTheme $active_theme) {
    $this->activeTheme = $active_theme;
    if ($active_theme) {
      $this->themeInitialization->loadActiveTheme($active_theme);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render($hook, array $variables) {
    static $default_attributes;

    $active_theme = $this->getActiveTheme();

    // If called before all modules are loaded, we do not necessarily have a
    // full theme registry to work with, and therefore cannot process the theme
    // request properly. See also \Drupal\Core\Theme\Registry::get().
    if (!$this->moduleHandler->isLoaded() && !defined('MAINTENANCE_MODE')) {
      throw new \Exception('The theme implementations may not be rendered until all modules are loaded.');
    }

    $theme_registry = $this->themeRegistry->getRuntime();

    // While we search for templates, we create a list of template suggestions
    // to use for Twig debug comments. Note: this list is entirely separate from
    // the list of $suggestions created later that is passed to
    // theme_suggestions alter hooks.
    $template_suggestions = [$hook];

    // If an array of hook candidates were passed, use the first one that has an
    // implementation.
    if (is_array($hook)) {
      // Ensure $template_suggestions has continuous numeric keys.
      $template_suggestions = array_values($hook);
      foreach ($hook as $candidate) {
        if ($theme_registry->has($candidate)) {
          break;
        }
      }
      $hook = $candidate;
    }
    // Save the original theme hook, so it can be supplied to theme variable
    // preprocess callbacks.
    $original_hook = $hook;

    // If there's no implementation, check for more generic fallbacks.
    // If there's still no implementation, log an error and return an empty
    // string.
    if (!$theme_registry->has($hook)) {
      // Iteratively strip everything after the last '__' delimiter, until an
      // implementation is found.
      while ($pos = strrpos($hook, '__')) {
        $hook = substr($hook, 0, $pos);
        if ($theme_registry->has($hook)) {
          break;
        }
      }
      if (!$theme_registry->has($hook)) {
        // Only log a message when not trying theme suggestions ($hook being an
        // array).
        if (!isset($candidate)) {
          \Drupal::logger('theme')->warning('Theme hook %hook not found.', ['%hook' => $hook]);
        }
        // There is no theme implementation for the hook passed. Return FALSE so
        // the function calling
        // \Drupal\Core\Theme\ThemeManagerInterface::render() can differentiate
        // between a hook that exists and renders an empty string, and a hook
        // that is not implemented.
        return FALSE;
      }
    }

    $info = $theme_registry->get($hook);

    // If $hook is an array of strings, the code above only expands the final
    // element in the array. We need to let the template engine know about all
    // possible $template_suggestions (for discoverability), so we grab the last
    // element and expand it.
    $hook_name = $template_suggestions[array_key_last($template_suggestions)];
    while ($pos = strrpos($hook_name, '__')) {
      $hook_name = substr($hook_name, 0, $pos);
      $template_suggestions[] = $hook_name;
    }

    // If a renderable array is passed as $variables, then set $variables to
    // the arguments expected by the theme function.
    if (isset($variables['#theme']) || isset($variables['#theme_wrappers'])) {
      $element = $variables;
      $variables = [];
      if (isset($info['variables'])) {
        foreach (array_keys($info['variables']) as $name) {
          if (isset($element["#$name"]) || array_key_exists("#$name", $element)) {
            $variables[$name] = $element["#$name"];
          }
        }
      }
      else {
        $variables[$info['render element']] = $element;
        // Give a hint to render engines to prevent infinite recursion.
        $variables[$info['render element']]['#render_children'] = TRUE;
      }
    }

    // Merge in argument defaults.
    if (!empty($info['variables'])) {
      $variables += $info['variables'];
    }
    elseif (!empty($info['render element'])) {
      $variables += [$info['render element'] => []];
    }
    // Supply original caller info.
    $variables += [
      'theme_hook_original' => $original_hook,
    ];

    // Set base hook for later use. For example if '#theme' => 'node__article'
    // is called, we run hook_theme_suggestions_node_alter() rather than
    // hook_theme_suggestions_node__article_alter(), and also pass in the base
    // hook as the last parameter to the suggestions alter hooks.
    if (isset($info['base hook'])) {
      $base_theme_hook = $info['base hook'];
    }
    else {
      $base_theme_hook = $hook;
    }

    // Invoke hook_theme_suggestions_HOOK().
    $suggestions = $this->moduleHandler->invokeAll('theme_suggestions_' . $base_theme_hook, [$variables]);
    // Make a copy of the initial suggestions for later.
    $suggestions_copy = $suggestions;
    // If the theme implementation was invoked with a direct theme suggestion
    // like '#theme' => 'node__article', add it to the suggestions array before
    // invoking suggestion alter hooks.
    if (isset($info['base hook'])) {
      $suggestions[] = $hook;
      $suggestions_include_specific_hook = TRUE;
    }
    else {
      $suggestions_include_specific_hook = FALSE;
    }

    // Invoke hook_theme_suggestions_alter() and
    // hook_theme_suggestions_HOOK_alter().
    $hooks = [
      'theme_suggestions',
      'theme_suggestions_' . $base_theme_hook,
    ];
    $this->moduleHandler->alter($hooks, $suggestions, $variables, $base_theme_hook);
    $this->alter($hooks, $suggestions, $variables, $base_theme_hook);

    // In order to properly merge these new suggestions into our previous list
    // of all possible template suggestions, we need to determine where each
    // suggestion comes from and order them like so:
    //
    // 1. More-specific suggestions from the #theme list that aren't for the
    //    $base_theme_hook.
    // 2. Suggestions for $base_theme_hook from:
    //    a. hook_theme_suggestions_alter(), hook_theme_suggestions_HOOK_alter()
    //    b. #theme list
    //    c. hook_theme_suggestions_HOOK()
    //    d. $base_theme_hook
    // 3. Less-specific suggestions from the #theme array that aren't for the
    //    $base_theme_hook.
    //
    // Since 2a and 2c come from $suggestions, we need to figure out how to
    // split that array into two parts.
    if ($suggestions_include_specific_hook) {
      $separator_hook = $hook;
      // Ensure numeric keys are continuous to make slicing the array easier.
      $reindexed_suggestions = array_values($suggestions);
    }
    else {
      // Since the $hook is not included in $suggestions, the only guaranteed
      // way to split the list is to re-run the alter hooks with a dummy
      // separator inserted into the suggestions list.
      $separator_hook = $base_theme_hook . '__do.not.remove__hook_separator';
      $suggestions_copy[] = $separator_hook;
      $this->moduleHandler->alter($hooks, $suggestions_copy, $variables, $base_theme_hook);
      $this->alter($hooks, $suggestions_copy, $variables, $base_theme_hook);
      $reindexed_suggestions = array_values($suggestions_copy);
    }
    // Find the separator in the suggestions list.
    $split_position = array_search($separator_hook, $reindexed_suggestions);

    // Split the suggestions into the two needed lists (see above).
    $least_specific_suggestions = array_slice(
      $reindexed_suggestions,
      0,
      ($split_position === FALSE) ? 0 : $split_position
    );
    $most_specific_suggestions = array_slice(
      $reindexed_suggestions,
      // If the separator was not found, then we assume any #theme array
      // suggestions inserted into $suggestions would also be removed. So, all
      // suggestions are more specific than the #theme array suggestions.
      ($split_position === FALSE) ? 0 : $split_position + 1
    );

    // The $hook's theme registry may specify a "base hook" that differs from
    // the base string of $hook. If so, we need to search $template_suggestions
    // for both of these base hook strings.
    $base_of_hook = explode('__', $hook)[0];

    // Scan through the template suggestions from the end to the beginning to
    // find the first and last occurrence of the base hook.
    $first_base_hook_key = FALSE;
    foreach (array_reverse($template_suggestions, TRUE) as $key => $hook_name) {
      // Find the base hook for this hook suggestion.
      $base_hook_name = explode('__', $hook_name)[0];
      if ($base_hook_name === $base_theme_hook || $base_hook_name === $base_of_hook) {
        if ($first_base_hook_key === FALSE) {
          // Since we are searching from the end of $template_suggestions, we
          // have just now found the last occurrence of the base hook. Insert
          // the least specific suggestions just before this last occurrence of
          // the base hook, assuming it is the base theme hook. However, under
          // some edge cases, the last occurrence of the base hook will be a
          // specific suggestion and won't be the actual base hook, so we need
          // to insert the suggestions after this last occurrence.
          $insertion_point = $hook_name === $base_theme_hook ? $key : $key + 1;
          $prevent_duplicate = in_array($hook_name, $least_specific_suggestions) ? 1 : 0;
          array_splice(
            $template_suggestions,
            $insertion_point,
            $prevent_duplicate,
            array_reverse($least_specific_suggestions)
          );
        }
        // Keep searching for the first occurrence of the base hook.
        $first_base_hook_key = $key;
      }
    }
    // Insert the most specific suggestions just before the first occurrence of
    // the base hook.
    array_splice(
      $template_suggestions,
      $first_base_hook_key,
      0,
      array_reverse($most_specific_suggestions)
    );

    // Check if each suggestion exists in the theme registry, and if so,
    // use it instead of the base hook. For example, a function may use
    // '#theme' => 'node', but a module can add 'node__article' as a suggestion
    // via hook_theme_suggestions_HOOK_alter(), enabling a theme to have
    // an alternate template file for article nodes.
    $template_suggestion = $hook;
    foreach (array_reverse($suggestions) as $suggestion) {
      if ($theme_registry->has($suggestion)) {
        $template_suggestion = $suggestion;
        $info = $theme_registry->get($suggestion);
        break;
      }
    }

    // Include a file if the theme function or variable preprocessor is held
    // elsewhere.
    if (!empty($info['includes'])) {
      foreach ($info['includes'] as $include_file) {
        include_once $this->root . '/' . $include_file;
      }
    }

    // Invoke the variable preprocessors, if any.
    if (isset($info['base hook'])) {
      $base_hook = $info['base hook'];
      $base_hook_info = $theme_registry->get($base_hook);
      // Include files required by the base hook, since its variable
      // preprocessors might reside there.
      if (!empty($base_hook_info['includes'])) {
        foreach ($base_hook_info['includes'] as $include_file) {
          include_once $this->root . '/' . $include_file;
        }
      }
      if (isset($base_hook_info['preprocess functions'])) {
        // Set a variable for the 'theme_hook_suggestion'. This is used to
        // maintain backwards compatibility with template engines.
        $theme_hook_suggestion = $hook;
      }
    }
    if (isset($info['preprocess functions'])) {
      foreach ($info['preprocess functions'] as $preprocessor_function) {
        if (function_exists($preprocessor_function)) {
          $preprocessor_function($variables, $hook, $info);
        }
      }
      // Allow theme preprocess functions to set $variables['#attached'] and
      // $variables['#cache'] and use them like the corresponding element
      // properties on render arrays. In Drupal 8, this is the (only) officially
      // supported method of attaching bubbleable metadata from preprocess
      // functions. Assets attached here should be associated with the template
      // that we are preprocessing variables for.
      $preprocess_bubbleable = [];
      foreach (['#attached', '#cache'] as $key) {
        if (isset($variables[$key])) {
          $preprocess_bubbleable[$key] = $variables[$key];
        }
      }
      // We do not allow preprocess functions to define cacheable elements.
      unset($preprocess_bubbleable['#cache']['keys']);
      if ($preprocess_bubbleable) {
        // @todo Inject the Renderer in https://www.drupal.org/node/2529438.
        \Drupal::service('renderer')->render($preprocess_bubbleable);
      }
    }

    // Generate the output using either a function or a template.
    $output = '';
    if (isset($info['function'])) {
      if (function_exists($info['function'])) {
        // Theme functions do not render via the theme engine, so the output is
        // not autoescaped. However, we can only presume that the theme function
        // has been written correctly and that the markup is safe.
        $output = Markup::create($info['function']($variables));
      }
    }
    else {
      $render_function = 'twig_render_template';
      $extension = '.html.twig';

      // The theme engine may use a different extension and a different
      // renderer.
      $theme_engine = $active_theme->getEngine();
      if (isset($theme_engine)) {
        if ($info['type'] != 'module') {
          if (function_exists($theme_engine . '_render_template')) {
            $render_function = $theme_engine . '_render_template';
          }
          $extension_function = $theme_engine . '_extension';
          if (function_exists($extension_function)) {
            $extension = $extension_function();
          }
        }
      }

      // In some cases, a template implementation may not have had
      // template_preprocess() run (for example, if the default implementation
      // is a function, but a template overrides that default implementation).
      // In these cases, a template should still be able to expect to have
      // access to the variables provided by template_preprocess(), so we add
      // them here if they don't already exist. We don't want the overhead of
      // running template_preprocess() twice, so we use the 'directory' variable
      // to determine if it has already run, which while not completely
      // intuitive, is reasonably safe, and allows us to save on the overhead of
      // adding some new variable to track that.
      if (!isset($variables['directory'])) {
        $default_template_variables = [];
        template_preprocess($default_template_variables, $hook, $info);
        $variables += $default_template_variables;
      }
      if (!isset($default_attributes)) {
        $default_attributes = new Attribute();
      }
      foreach ([
        'attributes',
        'title_attributes',
        'content_attributes',
      ] as $key) {
        if (isset($variables[$key]) && !($variables[$key] instanceof Attribute)) {
          if ($variables[$key]) {
            $variables[$key] = new Attribute($variables[$key]);
          }
          else {
            // Create empty attributes.
            $variables[$key] = clone $default_attributes;
          }
        }
      }

      // Render the output using the template file.
      $template_file = $info['template'] . $extension;
      if (isset($info['path'])) {
        $template_file = $info['path'] . '/' . $template_file;
      }
      // Add the theme suggestions to the variables array just before rendering
      // the template for backwards compatibility with template engines.
      $variables['theme_hook_suggestions'] = $suggestions;
      // For backwards compatibility, pass 'theme_hook_suggestion' on to the
      // template engine. This is only set when calling a direct suggestion like
      // '#theme' => 'menu__shortcut_default' when the template exists in the
      // current theme.
      if (isset($theme_hook_suggestion)) {
        $variables['theme_hook_suggestion'] = $theme_hook_suggestion;
      }
      // Add two read-only variables that help the template engine understand
      // how the template was chosen from among all suggestions.
      $variables['template_suggestions'] = $template_suggestions;
      $variables['template_suggestion'] = $template_suggestion;
      $output = $render_function($template_file, $variables);
    }

    return ($output instanceof MarkupInterface) ? $output : (string) $output;
  }

  /**
   * Initializes the active theme for a given route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  protected function initTheme(RouteMatchInterface $route_match = NULL) {
    // Determine the active theme for the theme negotiator service. This
    // includes the default theme as well as really specific ones like the ajax
    // base theme.
    if (!$route_match) {
      $route_match = \Drupal::routeMatch();
    }
    if ($route_match instanceof StackedRouteMatchInterface) {
      $route_match = $route_match->getMasterRouteMatch();
    }
    $theme = $this->themeNegotiator->determineActiveTheme($route_match);
    $this->activeTheme = $this->themeInitialization->initTheme($theme);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Should we cache some of these information?
   */
  public function alterForTheme(ActiveTheme $theme, $type, &$data, &$context1 = NULL, &$context2 = NULL) {
    // Most of the time, $type is passed as a string, so for performance,
    // normalize it to that. When passed as an array, usually the first item in
    // the array is a generic type, and additional items in the array are more
    // specific variants of it, as in the case of array('form', 'form_FORM_ID').
    if (is_array($type)) {
      $extra_types = $type;
      $type = array_shift($extra_types);
      // Allow if statements in this function to use the faster isset() rather
      // than !empty() both when $type is passed as a string, or as an array
      // with one item.
      if (empty($extra_types)) {
        unset($extra_types);
      }
    }

    $theme_keys = array_keys($theme->getBaseThemeExtensions());
    $theme_keys[] = $theme->getName();
    $functions = [];
    foreach ($theme_keys as $theme_key) {
      $function = $theme_key . '_' . $type . '_alter';
      if (function_exists($function)) {
        $functions[] = $function;
      }
      if (isset($extra_types)) {
        foreach ($extra_types as $extra_type) {
          $function = $theme_key . '_' . $extra_type . '_alter';
          if (function_exists($function)) {
            $functions[] = $function;
          }
        }
      }
    }

    foreach ($functions as $function) {
      $function($data, $context1, $context2);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    $theme = $this->getActiveTheme();
    $this->alterForTheme($theme, $type, $data, $context1, $context2);
  }

}
