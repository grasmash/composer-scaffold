<?php

namespace Grasmash\ComposerScaffold;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The "composer:scaffold" command class.
 *
 * Composer scaffold files and generates the autoload.php file.
 */
class ComposerScaffoldCommand extends BaseCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setName('composer:scaffold')->setDescription('Update the Composer scaffold files.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $handler = new Handler($this->getComposer(), $this->getIO());
    $handler->scaffold();
  }

}
