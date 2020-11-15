<?php

namespace Drupal\Core\Ajax;

/**
 * An AJAX command for adding JS to the page via ajax.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.add_js()
 * defined in misc/ajax.js.
 *
 * @see misc/ajax.js
 *
 * @ingroup ajax
 */
class AddJsCommand implements CommandInterface {

  /**
   * An array containing the attributes of the 'script' tags to be added to the
   * page.
   *
   * @var string[]
   */
  protected $scripts;

  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string|null
   */
  protected $selector;

  /**
   * Constructs an AddJsCommand.
   *
   * @param array $scripts
   *   An array containing the attributes of the 'script' tags to be added to
   *   the page.
   * @param string|null $selector
   *   A CSS selector.
   */
  public function __construct(array $scripts, $selector = 'body') {
    $this->scripts = $scripts;
    $this->selector = $selector;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'add_js',
      'selector' => $this->selector,
      'data' => $this->scripts,
    ];
  }

}
