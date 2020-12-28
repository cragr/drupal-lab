<?php

namespace Drupal\theme_test\Element;

use Drupal\Core\Render\Annotation\RenderElement;
use Drupal\Core\Render\Element\Container;

/**
 * Class ThemeTestRenderElementContext.
 *
 * @RenderElement("theme_test_render_element_context")
 */
class ThemeTestRenderElementContext extends Container {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return ['#theme_wrappers' => ['theme_test_render_element_context']] + parent::getInfo();
  }

}
