<?php

namespace Drupal\position_shim_test\Controller;

use Drupal\Core\Controller\ControllerBase;

class PositionShimTestPortedJqueryTestsController extends ControllerBase {

  /**
   * Provides a page with the jQuery UI position library for testing.
   *
   * @return array
   *   The render array.
   */
  public function build() {
    /**
     * <div id="el1" style="position: absolute; width: 6px; height: 6px;"></div>
    <div id="el2" style="position: absolute; width: 6px; height: 6px;"></div>
    <div id="parent" style="position: absolute; width: 6px; height: 6px; top: 4px; left: 4px;"></div>
    <div id="within" style="position: absolute; width: 12px; height: 12px; top: 2px; left: 0px;"></div>

    <div id="scrollx" style="position: absolute; top: 0px; left: 0px">
    <div id="elx" style="position: absolute; width: 10px; height: 10px;"></div>
    <div id="parentx" style="position: absolute; width: 20px; height: 20px; top: 40px; left: 40px;"></div>
    </div>

    <div style="position: absolute; height: 5000px; width: 5000px;"></div>

    <div id="fractions-parent" style="position: absolute; left: 10.7432222px; top: 10.532325px; height: 30px; width: 201px;">
    <div id="fractions-element"></div>
    </div>

    <div id="bug-5280" style="height: 30px; width: 201px;">
    <div style="width: 50px; height: 10px;"></div>
    </div>

    <div id="bug-8710-within-smaller" style="position: absolute; width: 100px; height: 99px; top: 0px; left: 0px;"></div>
    <div id="bug-8710-within-bigger" style="position: absolute; width: 100px; height: 101px; top: 0px; left: 0px;"></div>
     */
    return [
      'el1' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'el1',
          'style' => 'position: absolute; width: 6px; height: 6px;',
        ],
      ],
      'el2' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'el2',
          'style' => 'position: absolute; width: 6px; height: 6px;'
        ],
      ],
      'parent' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'parent',
          'style' => 'position: absolute; width: 6px; height: 6px; top: 4px; left: 4px;'
        ],
      ],
      'within' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'within',
          'style' => 'position: absolute; width: 12px; height: 12px; top: 2px; left: 0px;'
        ],
      ],
      'scrollx' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'scrollx',
          'style' => 'position: absolute; top: 0px; left: 0px;',
        ],
        'elx' => [
          '#type' => 'container',
          '#attributes' => [
            'id' => 'elx',
            'style' => 'position: absolute; width: 10px; height: 10px;'
          ],
        ],
        'parentx' => [
          '#type' => 'container',
          '#attributes' => [
            'id' => 'parentx',
            'style' => 'position: absolute; width: 20px; height: 20px; top: 40px; left: 40px;'
          ],
        ],
      ],
      'largebox' => [
        '#type' => 'container',
        '#attributes' => [
          'style' => 'position: absolute; height: 5000px; width: 5000px;',
        ],
      ],
      'fractionsparent' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'fractions-parent',
          'style' => 'position: absolute; left: 10.7432222px; top: 10.532325px; height: 30px; width: 201px;',
        ],
        'fractionselement' => [
          '#type' => 'container',
          '#attributes' => [
            'id' => 'fractions-element',
          ],
        ],
      ],
      'bug5280' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'bug-5280',
          'style' => 'height: 30px; width: 201px;',
        ],
        'child' => [
          '#type' => 'container',
          '#attributes' => [
            'style' => 'width: 50px; height: 10px;',
          ],
        ],
      ],
      'bug8710withinsmaller' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'bug-8710-within-smaller',
          'style' => 'position: absolute; width: 100px; height: 99px; top: 0px; left: 0px;',
        ],
      ],
      'bug8710withinbugger' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'bug-8710-within-bigger',
          'style' => 'position: absolute; width: 100px; height: 101px; top: 0px; left: 0px;',
        ],
      ],
      '#attached' => [
        'library' => [
          'core/jquery.ui.position',
          'position_shim_test/position.shim.test',
        ],
      ],
    ];
  }

}
