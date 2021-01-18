<?php

namespace Drupal\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates a new theme based on latest default markup.
 */
class GenerateTheme extends Command {

  /**
   * Default mode for new directories.
   *
   * @todo should this be configurable?
   */
  const CHMOD_DIRECTORY = 0775;

  /**
   * Default mode for new files.
   *
   * @todo should this be configurable?
   */
  const CHMOD_FILE = 0664;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('generate-theme')
      ->setDescription('Generates a new theme based on latest default markup.')
      ->addArgument('machine-name', InputArgument::REQUIRED, 'The machine name of the generated theme');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new SymfonyStyle($input, $output);

    // Change the directory to the Drupal root.
    chdir(dirname(__DIR__, 5));

    // Path where the generated theme should be placed.
    // @todo allow configuring this.
    $destination_theme = $input->getArgument('machine-name');
    $destination = "themes/$destination_theme";

    if (is_dir($destination)) {
      $io->getErrorStyle()->error('Theme could not be generated because the destination directory exists already.');
      return 1;
    }

    // Source directory for the theme.
    // @todo allow configuring this.
    // @todo create new theme specifically for this purpose.
    $source_theme = 'classy';
    // @todo should we find the source directory based on theme machine name?
    $source = "core/themes/$source_theme";

    if (!is_dir($source)) {
      $io->getErrorStyle()->error("Theme could not be generated because the source directory $source does not exist.");
      return 1;
    }

    if (!$this->copyRecursive($source, $destination)) {
      // @todo better error message
      $io->getErrorStyle()->error('The theme could not be generated');
      return 1;
    }

    // Rename files based on the theme machine name.
    $file_pattern = "/$source_theme\.(theme|[^.]+\.yml)/";
    if ($files = @scandir($destination)) {
      foreach ($files as $file) {
        $location = $destination . '/' . $file;
        if (is_dir($location)) {
          continue;
        }

        if (preg_match($file_pattern, $file, $matches)) {
          if (!@rename($location, $destination . '/' . $destination_theme . '.' . $matches[1])) {
            $io->getErrorStyle()->error("The file $location could not be moved.");
            return 1;
          }
        }
      }
    }
    else {
      $io->getErrorStyle()->error("The destination directory $destination cannot be opened");
      return 1;
    }

    return 0;
  }

  /**
   * Copies files recursively.
   *
   * @param string $src
   *   A file or directory to be copied.
   * @param string $dest
   *   Destination directory where the directory or file should be copied.
   *
   * @return bool
   */
  private function copyRecursive($src, $dest) {
    // Copy all subdirectories and files.
    if (is_dir($src)) {
      if (!mkdir($dest, static::CHMOD_DIRECTORY, FALSE)) {
        return FALSE;
      }
      $handle = @opendir($src);
      while ($file = readdir($handle)) {
        if ($file != "." && $file != "..") {
          if ($this->copyRecursive("$src/$file", "$dest/$file") !== TRUE) {
            return FALSE;
          }
        }
      }
      closedir($handle);
    }
    elseif (is_link($src)) {
      symlink(readlink($src), $dest);
    }
    elseif (!@copy($src, $dest)) {
      return FALSE;
    }

    // Set permissions for the directory or file.
    if (!is_link($dest)) {
      if (is_dir($dest)) {
        $mode = static::CHMOD_DIRECTORY;
      }
      else {
        $mode = static::CHMOD_FILE;
      }

      if (!@chmod($dest, $mode)) {
        throw new \RuntimeException("The file permissions could not be set on $src");
      }
    }

    return TRUE;
  }

}
