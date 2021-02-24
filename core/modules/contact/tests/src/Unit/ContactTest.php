<?php

/**
 * @file
 * Contains \Drupal\Tests\contact\Unit\ContactTest.
 */

namespace Drupal\Tests\contact\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @group contact
 */
class ContactTest extends UnitTestCase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['contact'];

  /**
   * Test that contact_menu_local_tasks_alter throws Undefined index: tabs.
   *
   * @expectedException PHPUnit\Framework\Error\Notice
   * @expectedExceptionMessage Undefined index: tabs
   */
  public function testLocalTasksAlter() {
    include 'modules/contact/contact.module';
    $data = [];
    $route_name = 'entity.user.canonical';
    \contact_menu_local_tasks_alter($data, $route_name);
  }
}