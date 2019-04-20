<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;

/**
 * Scaffold operation to skip a scaffold file (do nothing).
 */
class SkipOp implements OperationInterface {

  /**
   * Process the 'skip' operation (just note that we deliberately did nothing).
   */
  public function process(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    $interpolator = $scaffold_file->getInterpolator();

    $io->write($interpolator->interpolate("  - Skip <info>[dest-rel-path]</info>: disabled"));
  }

}
