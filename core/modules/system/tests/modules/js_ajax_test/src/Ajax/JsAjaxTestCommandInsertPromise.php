<?php

namespace Drupal\js_ajax_test\Ajax;

use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\CommandInterface;

/**
 * Test Ajax command.
 */
class JsAjaxTestCommandInsertPromise extends AppendCommand {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {

    return [
      'command' => 'jsAjaxTestInsertPromise',
      'method' => 'append',
      'selector' => $this->selector,
      'data' => $this->getRenderedContent(),
      'settings' => $this->settings,
    ];
  }

}
