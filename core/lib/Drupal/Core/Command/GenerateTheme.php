<?php

namespace Drupal\Core\Command;

use Drupal\Core\File\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Util\TemplateDirIterator;

/**
 * Generates a new theme based on latest default markup.
 */
class GenerateTheme extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('generate-theme')
      ->setDescription('Generates a new theme based on latest default markup.')
      ->addArgument('machine-name', InputArgument::REQUIRED, 'The machine name of the generated theme')
      ->addOption('name', NULL, InputOption::VALUE_OPTIONAL, 'A name for the theme.')
      ->addOption('description', NULL, InputOption::VALUE_OPTIONAL, 'A description of your theme.')
      ->addOption('path', NULL, InputOption::VALUE_OPTIONAL, 'The path where your theme will be created. Defaults to: themes');
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
    $default_destination = 'themes';
    $destination = trim($input->getOption('path') ?: $default_destination, '/') . '/' . $destination_theme;

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
      // @todo should we reverse changes in a case where something fails?
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
      $io->getErrorStyle()->error("The destination directory $destination cannot be opened.");
      return 1;
    }

    // Info file.
    $info_file = "$destination/$destination_theme.info.yml";
    if (file_exists($info_file)) {
      $info_file_contents = file_get_contents($info_file);
      $name = $input->getOption('description') ?: $destination_theme;
      $info_file_contents = preg_replace("/(name:).*/", "$1 $name", $info_file_contents);
      if ($description = $input->getOption('description')) {
        // @todo should we ensure that description exists since it's not required?
        $info_file_contents = preg_replace("/(description:).*/", "$1 '$description'", $info_file_contents);
      }
      // Replace references to libraries.
      $info_file_contents = preg_replace("/$source_theme(\/[^\/]+(\n|$))/", "$destination_theme$1", $info_file_contents);

      if (!@file_put_contents($info_file, $info_file_contents)) {
        $io->getErrorStyle()->error("The theme info file $info_file could not be written.");
        return 1;
      }
    }
    else {
      $io->getErrorStyle()->error("The theme info file $info_file could not be read.");
      return 1;
    }

    // Rename hooks.
    $theme_file = "$destination/$destination_theme.theme";
    if (file_exists($theme_file)) {
      if (!@file_put_contents($theme_file, preg_replace("/(function )($source_theme)(_.*)/", "$1$destination_theme$3", file_get_contents($theme_file)))) {
        $io->getErrorStyle()->error("The theme file $theme_file could not be written.");
        return 1;
      }
    }

    // Rename references to libraries in templates.
    $iterator = new TemplateDirIterator(new \RegexIterator(
      new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($destination), \RecursiveIteratorIterator::LEAVES_ONLY
      ), '/' . preg_quote('.html.twig') . '$/'
    ));

    foreach ($iterator as $template_file => $contents) {
      $new_template_content = preg_replace("/(attach_library\(['\")])$source_theme(\/.*['\"]\))/", "$1$destination_theme$2", $contents);
      if (!@file_put_contents($template_file, $new_template_content)) {
        $io->getErrorStyle()->error("The template file $template_file could not be written.");
        return 1;
      }
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
  private function copyRecursive($src, $dest): bool {
    // Copy all subdirectories and files.
    if (is_dir($src)) {
      if (!mkdir($dest, FileSystem::CHMOD_DIRECTORY, FALSE)) {
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
        $mode = FileSystem::CHMOD_DIRECTORY;
      }
      else {
        $mode = FileSystem::CHMOD_FILE;
      }

      if (!@chmod($dest, $mode)) {
        throw new \RuntimeException("The file permissions could not be set on $src");
      }
    }

    return TRUE;
  }

}
