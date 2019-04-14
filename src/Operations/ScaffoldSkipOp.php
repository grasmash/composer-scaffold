<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Composer\Composer;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;

/**
 * Scaffold operation to skip a scaffold file (do nothing).
 */
class ScaffoldSkipOp implements ScaffoldOperationInterface {

  /**
   * Process the 'skip' operation (just note that we deliberately did nothing).
   */
  public function process(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    $interpolator = $scaffold_file->getInterpolator();

    $io->write($interpolator->interpolate("  - Skip scaffold file <info>[dest-rel-path]</info>"));
  }

}
