<?php

namespace Drupal\Tests\views\Kernel\Element;

use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Element\InvalidViewException;

/**
 * Tests the view render element.
 *
 * @group views
 */
class ViewElementTest extends ViewsKernelTestBase {

  /**
   * Tests that an exception is thrown when an invalid View is passed.
   */
  public function testInvalidView() {
    $renderer = $this->container->get('renderer');
    $render_element = [
      '#type' => 'view',
      '#name' => 'invalid_view_name',
      '#embed' => FALSE,
    ];
    $this->expectException(InvalidViewException::class);
    $this->expectExceptionMessage("Invalid View name ({$render_element['#name']}) given.");
    $renderer->renderRoot($render_element);
  }

}
