<?php

namespace Drupal\Core\StringTranslation\Translator;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Component\Gettext\PoMemoryWriter;
use Drupal\Core\File\FileSystemInterface;

/**
 * File based string translation.
 *
 * Translates a string when some systems are not available.
 *
 * Used during the install process, when database, theme, and localization
 * system is possibly not yet available.
 */
class FileTranslation extends StaticTranslation {

  /**
   * Directory to find translation files in the file system.
   *
   * @var string
   */
  protected $directory;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The filename pattern of the translation files.
   *
   * @var string
   */
  protected $filenamePattern;

  /**
   * Constructs a StaticTranslation object.
   *
   * @param string $directory
   *   The directory to retrieve file translations from.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param string $filename_pattern
   *   The filename pattern of the translation files. The following placeholders
   *   can be used for compatibility with
   *   locale_translation_build_server_pattern():
   *   - "%project": Will be replaced with "drupal".
   *   - "%version": Will be replaced with a pattern matching versions.
   *   - "%core": Will be replaced with "all".
   *   - "%language": Will be replaced with the given language code or a pattern
   *     matching language codes.
   *
   * @see locale_translation_build_server_pattern()
   */
  public function __construct($directory, FileSystemInterface $file_system, $filename_pattern = NULL) {
    parent::__construct();
    $this->directory = $directory;
    $this->fileSystem = $file_system;
    if (!isset($filename_pattern)) {
      @trigger_error('Constructing a \Drupal\Core\StringTranslation\Translator\FileTranslation instance without a $filename_pattern argument is deprecated in drupal:9.2.0. The $filename argument will be required in drupal:10.0.0. See https://www.drupal.org/node/XXXXXXX', E_USER_DEPRECATED);
      $filename_pattern = '%project-%version.%language.po';
    }
    $this->filenamePattern = $filename_pattern;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguage($langcode) {
    // If the given langcode was selected, there should be at least one .po
    // file with its name in the pattern drupal-$version.$langcode.po.
    // This might or might not be the entire filename. It is also possible
    // that multiple files end with the same suffix, even if unlikely.
    $files = $this->findTranslationFiles($langcode);

    if (!empty($files)) {
      return $this->filesToArray($langcode, $files);
    }
    else {
      return [];
    }
  }

  /**
   * Finds installer translations either for a specific or all languages.
   *
   * Filenames must match the pattern:
   *  - 'drupal-[version].[langcode].po (if langcode is provided)
   *  - 'drupal-[version].*.po (if no langcode is provided)
   *
   * @param string $langcode
   *   (optional) The language code corresponding to the language for which we
   *   want to find translation files. If omitted, information on all available
   *   files will be returned.
   *
   * @return array
   *   An associative array of file information objects keyed by file URIs as
   *   returned by FileSystemInterface::scanDirectory().
   *
   * @see \Drupal\Core\File\FileSystemInterface::scanDirectory()
   */
  public function findTranslationFiles($langcode = NULL) {
    $files = [];
    if (is_dir($this->directory)) {
      $files = $this->fileSystem->scanDirectory($this->directory, $this->getTranslationFilesPattern($langcode), ['recurse' => FALSE]);
    }
    return $files;
  }

  /**
   * Provides translation file name pattern.
   *
   * @param string $langcode
   *   (optional) The language code corresponding to the language for which we
   *   want to find translation files.
   *
   * @return string
   *   String file pattern.
   */
  protected function getTranslationFilesPattern($langcode = NULL) {
    // The file name matches the configured pattern, by default:
    // "drupal-[release version].[language code].po". If provided, the given
    // $langcode is used as the language code, otherwise all language codes will
    // match.
    $variables = [
      '%project' => 'drupal',
      '%version' => '[0-9a-z\.-]+',
      '%core' => 'all',
      '%language' => $langcode ? preg_quote($langcode, '!') : '[^\.]+',
    ];
    return '!' . strtr(preg_quote($this->filenamePattern, '!'), $variables) . '$!';
  }

  /**
   * Reads the given Gettext PO files into a data structure.
   *
   * @param string $langcode
   *   Language code string.
   * @param array $files
   *   List of file objects with URI properties pointing to read.
   *
   * @return array
   *   Structured array as produced by a PoMemoryWriter.
   *
   * @see \Drupal\Component\Gettext\PoMemoryWriter
   */
  public static function filesToArray($langcode, array $files) {
    $writer = new PoMemoryWriter();
    $writer->setLangcode($langcode);
    foreach ($files as $file) {
      $reader = new PoStreamReader();
      $reader->setURI($file->uri);
      $reader->setLangcode($langcode);
      $reader->open();
      $writer->writeItems($reader, -1);
    }
    return $writer->getData();
  }

}
