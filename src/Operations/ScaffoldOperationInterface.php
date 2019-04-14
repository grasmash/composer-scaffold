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
 * Data file that keeps track of one scaffold file's source, destination, and package.
 */
interface ScaffoldOperationInterface {
  // @todo: Should this be part of this interface?
  // public function interpolationData();

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
