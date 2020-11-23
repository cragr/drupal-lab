<?php

namespace Drupal\Tests\system\Kernel\Installer;

use Drupal\Core\StringTranslation\Translator\FileTranslation;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for installer language support.
 *
 * @group Installer
 */
class InstallTranslationFilePatternTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * @dataProvider providerTestFilesPattern
   */
  public function testFilesPattern($filename_pattern, $langcode, $filename, $match) {
    $file_translation = new FileTranslation('filename', $this->container->get('file_system'), $filename_pattern);
    $file_pattern_method = new \ReflectionMethod('\Drupal\Core\StringTranslation\Translator\FileTranslation', 'getTranslationFilesPattern');
    $file_pattern_method->setAccessible(TRUE);

    $pattern = $file_pattern_method->invoke($file_translation, $langcode);
    $this->assertSame($match, preg_match($pattern, $filename));
  }

  /**
   * @return array
   */
  public function providerTestFilesPattern() {
    return [
      ['%project-%version.%language.po', 'hu', 'drupal-8.0.0-alpha1.hu.po', 1],
      ['%project-%version.%language.po', 'ta', 'drupal-8.10.10-beta12.ta.po', 1],
      ['%project-%version.%language.po', 'hi', 'drupal-8.0.0.hi.po', 1],
      ['%project-%version.%language.po', 'hu', 'drupal-alpha1-*-hu.po', 0],
      ['%project-%version.%language.po', 'ta', 'drupal-beta12.ta', 0],
      ['%project-%version.%language.po', 'hi', 'drupal-hi.po', 0],
      ['%project-%version.%language.po', 'de', 'drupal-dummy-de.po', 0],
      ['%project-%version.%language.po', 'hu', 'drupal-10.0.1.alpha1-hu.po', 0],
      ['%project.%language.po', 'hu', 'drupal.hu.po', 1],
      ['%project.%language.po', 'hu', 'drupal-8.0.0-alpha1.hu.po', 0],
      ['%project-%core-%version.%language.po', 'hu', 'drupal-all-8.0.0-alpha1.hu.po', 1],
      ['%project-%core-%version.%language.po', 'hu', 'drupal-8.0.0-alpha1.hu.po', 0],
      ['%project-%version.%language!.po', 'hu', 'drupal-8.0.0-alpha1.hu!.po', 1],
      ['%project-%version.%language!.po', 'hu', 'drupal-8.0.0-alpha1.hu.po', 0],
      ['%project-%version.%language!.po', 'hu', 'drupal-8.0.0-alpha1.hu!!.po', 0],
      ['%project-%version.%language!.po', 'hu!', 'drupal-8.0.0-alpha1.hu!!.po', 1],
    ];
  }

}
