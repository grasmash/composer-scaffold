<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\ScaffoldFilePath;

/**
 * Scaffold operation to skip a scaffold file (do nothing).
 */
class SkipOp implements OperationInterface {

  /**
   * Skip the specified scaffold file.
   *
   * {@inheritdoc}
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, array $options) : ScaffoldResult {
    $interpolator = $destination->getInterpolator();
    $io->write($interpolator->interpolate("  - Skip <info>[dest-rel-path]</info>: disabled"));

    return (new ScaffoldResult($destination))->setManaged(FALSE);
  }

}
