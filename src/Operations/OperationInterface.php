<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\ScaffoldFilePath;
use Grasmash\ComposerScaffold\ScaffoldOptions;

/**
 * Data file that keeps track of one scaffold file's source, destination, and package.
 */
interface OperationInterface {

  /**
   * Process this scaffold operation.
   *
   * @param \Grasmash\ComposerScaffold\ScaffoldFilePath $destination
   *   Scaffold file's destination path.
   * @param \Composer\IO\IOInterface $io
   *   IOInterface to writing to.
   * @param \Grasmash\ComposerScaffold\ScaffoldOptions $options
   *   Various options that may alter the behavior of the operation.
   *
   * @return ScaffoldResult
   *   Result of the scaffolding operation (is this file managed or unmanaged, etc.)
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options) : ScaffoldResult;

}
