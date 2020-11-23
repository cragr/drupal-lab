<?php

namespace Drupal\KernelTests\Core\Installer;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\Translator\FileTranslation;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for installer language support.
 *
 * @group Installer
 */
class InstallerLanguageTest extends KernelTestBase {

  /**
   * Tests that the installer can find translation files.
   *
   * @param string[]
   *   A list of test files to create.
   * @param string $filename_pattern
   *   The filename pattern to search with.
   * @param string $langcode
   *   The language code of the files to search for.
   * @param string[] $files_expected
   *   The expected list of files that are found.
   *
   * @dataProvider providerTestInstallerTranslationFiles
   */
  public function testInstallerTranslationFiles($files, $filename_pattern, $langcode, $files_expected) {
    /* @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');
    $directory = $this->siteDirectory . '/translations';

    $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    foreach ($files as $file) {
      touch($directory . '/' . $file);
    }

    $file_translation = new FileTranslation($directory, $file_system, $filename_pattern);
    $files_found = $file_translation->findTranslationFiles($langcode);
    $this->assertCount(count($files_expected), $files_found);
    foreach ($files_found as $file) {
      $this->assertContains($file->filename, $files_expected);
    }
  }

  /**
   * Returns test cases for ::testInstallerTranslationFiles().
   *
   * @return array[]
   *   An array of test cases.
   */
  public function providerTestInstallerTranslationFiles() {
    // Different translation files would be found depending on which language
    // we are looking for.
    $tests['default-no-langcode'] = [
      'files' => ['drupal-8.0.0-beta2.hu.po', 'drupal-8.0.0.de.po'],
      'filename_pattern' => '%project-%version.%language.po',
      'langcode' => NULL,
      'files_found' => ['drupal-8.0.0-beta2.hu.po', 'drupal-8.0.0.de.po'],
    ];
    $tests['default-de'] = array_merge($tests['default-no-langcode'], [
      'langcode' => 'de',
      'files_found' => ['drupal-8.0.0.de.po'],
    ]);
    $tests['default-hu'] = array_merge($tests['default-no-langcode'], [
      'langcode' => 'hu',
      'files_found' => ['drupal-8.0.0-beta2.hu.po'],
    ]);
    $tests['default-it'] = array_merge($tests['default-no-langcode'], [
      'langcode' => 'it',
      'files_found' => [],
    ]);

    // Test with a non-default filename pattern.
    $tests['no-version-no-langcode'] = [
      'files' => ['drupal.hu.po', 'drupal.de.po'],
      'filename_pattern' => '%project.%language.po',
      'langcode' => NULL,
      'files_found' => ['drupal.hu.po', 'drupal.de.po'],
    ];
    $tests['no-version-de'] = array_merge($tests['no-version-no-langcode'], [
      'langcode' => 'de',
      'files_found' => ['drupal.de.po'],
    ]);
    $tests['no-version-hu'] = array_merge($tests['no-version-no-langcode'], [
      'langcode' => 'hu',
      'files_found' => ['drupal.hu.po'],
    ]);
    $tests['no-version-it'] = array_merge($tests['no-version-no-langcode'], [
      'langcode' => 'it',
      'files_found' => [],
    ]);

    // Test that the "%core" placeholder in the filename pattern works.
    $tests['core-no-langcode'] = [
      'files' => ['drupal-all-8.0.0-beta2.hu.po', 'drupal-all-8.0.0.de.po'],
      'filename_pattern' => '%project-%core-%version.%language.po',
      'langcode' => NULL,
      'files_found' => ['drupal-all-8.0.0-beta2.hu.po', 'drupal-all-8.0.0.de.po'],
    ];
    $tests['core-de'] = array_merge($tests['core-no-langcode'], [
      'langcode' => 'de',
      'files_found' => ['drupal-all-8.0.0.de.po'],
    ]);
    $tests['core-hu'] = array_merge($tests['core-no-langcode'], [
      'langcode' => 'hu',
      'files_found' => ['drupal-all-8.0.0-beta2.hu.po'],
    ]);
    $tests['core-it'] = array_merge($tests['core-no-langcode'], [
      'langcode' => 'it',
      'files_found' => [],
    ]);

    // Test that the regular expression delimiter is escaped properly.
    $tests['delimiter-no-langcode'] = [
      'files' => ['drupal!-8.0.0!.de!.po', 'drupal!-8.0.0!.de!!.po'],
      'filename_pattern' => '%project!-%version!.%language!.po',
      'langcode' => NULL,
      'files_found' => ['drupal!-8.0.0!.de!.po', 'drupal!-8.0.0!.de!!.po'],
    ];
    $tests['delimiter-de'] = array_merge($tests['delimiter-no-langcode'], [
      'langcode' => 'de',
      'files_found' => ['drupal!-8.0.0!.de!.po'],
    ]);
    $tests['delimiter-de!'] = array_merge($tests['delimiter-no-langcode'], [
      'langcode' => 'de!',
      'files_found' => ['drupal!-8.0.0!.de!!.po'],
    ]);
    $tests['delimiter-it'] = array_merge($tests['delimiter-no-langcode'], [
      'langcode' => 'it',
      'files_found' => [],
    ]);
    return $tests;
  }

  /**
   * Tests profile info caching in non-English languages.
   */
  public function testInstallerTranslationCache() {
    require_once 'core/includes/install.inc';

    // Prime the drupal_get_filename() static cache with the location of the
    // testing profile as it is not the currently active profile and we don't
    // yet have any cached way to retrieve its location.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    drupal_get_filename('profile', 'testing', 'core/profiles/testing/testing.info.yml');

    $info_en = install_profile_info('testing', 'en');
    $info_nl = install_profile_info('testing', 'nl');

    $this->assertNotContains('locale', $info_en['install'], 'Locale is not set when installing in English.');
    $this->assertContains('locale', $info_nl['install'], 'Locale is set when installing in Dutch.');
  }

}
