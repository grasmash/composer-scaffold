<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;

/**
 * Data file that keeps track of one scaffold file's source, destination, and package.
 */
interface OperationInterface {

  /**
   * Process this scaffold operation.
   *
   * @param \Grasmash\ComposerScaffold\ScaffoldFileInfo $scaffold_file
   *   Scaffold file to process.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface to writing to.
   * @param array $options
   *   Various options that may alter the behavior of the operation.
   */
  public function process(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options);

}
