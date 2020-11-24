<?php

namespace Drupal\Tests\auto_updates\Kernel\ReadinessChecker;

use Drupal\auto_updates\ReadinessChecker\DiskSpace;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests disk space readiness checking.
 *
 * @group auto_updates
 */
class DiskSpaceTest extends KernelTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['auto_updates'];

  /**
   * Tests the functionality of disk space readiness checks.
   */
  public function testDiskSpace():void {
    // No disk space issues.
    $disk_space = new DiskSpace($this->container->getParameter('app.root'));
    $messages = $disk_space->getErrors();
    $this->assertEmpty($messages);

    // Out of space.
    $disk_space = new TestDiskSpace($this->container->getParameter('app.root'));
    $messages = $disk_space->getErrors();
    $this->assertCount(1, $messages);
    $this->assertStringMatchesFormat('Logical disk "%s" has insufficient space. There must be at least %s megabytes free.', (string) $messages[0]);

    // Out of space not the same logical disk.
    $disk_space = new TestDiskSpaceNonSameDisk($this->container->getParameter('app.root'));
    $messages = $disk_space->getErrors();
    $this->assertCount(2, $messages);
    $this->assertStringMatchesFormat('Drupal root filesystem "%s" has insufficient space. There must be at least %s megabytes free.', (string) $messages[0]);
    $this->assertStringMatchesFormat('Vendor filesystem "%s" has insufficient space. There must be at least %s megabytes free.', (string) $messages[1]);

    // Web root and vendor path are invalid.
    $disk_space = new DiskSpace("if_there_was_ever_a_folder_with_this_path_this_test_would_fail");
    $messages = $disk_space->getErrors();
    $this->assertCount(2, $messages);
    $this->assertEquals('The web root could not be located.', (string) $messages[0]);
    $this->assertStringMatchesFormat('Vendor folder "if_there_was_ever_a_folder_with_this_path_this_test_would_fail/vendor" is not a valid directory. Alternate vendor folder locations are not currently supported.', (string) $messages[1]);
  }

}

/**
 * Test checker with the free disk space minimum set to a very high number.
 */
class TestDiskSpace extends DiskSpace {

  /**
   * {@inheritdoc}
   */
  const MINIMUM_DISK_SPACE = 99999999999999999999999999999999999999999999999999;

}

/**
 * A test checker that overrides TestDiskSpace to fake different logical disks.
 */
class TestDiskSpaceNonSameDisk extends TestDiskSpace {

  /**
   * {@inheritdoc}
   */
  protected function areSameLogicalDisk(string $root, string $vendor): bool {
    return FALSE;
  }

}
