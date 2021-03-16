<?php

namespace Drupal\Core\Ajax;

/**
 * AJAX command for focusing an element.
 *
 * @ingroup ajax
 */
class FocusFirstCommand implements CommandInterface {

  /**
   * The selector of the container with focusable elements.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs an FocusFirstCommand object.
   *
   * @param string $selector
   *   The selector of the container with focusable elements.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'focusFirst',
      'selector' => $this->selector,
    ];
  }

}
