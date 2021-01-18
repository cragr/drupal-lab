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
  }

}
