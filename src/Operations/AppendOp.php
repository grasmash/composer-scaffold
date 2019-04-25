<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\Interpolator;
use Grasmash\ComposerScaffold\ScaffoldFilePath;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Scaffold operation to add to the beginning and/or end of a scaffold file.
 */
class AppendOp implements OperationInterface, OriginalOpAwareInterface {

  use OriginalOpAwareTrait;

  protected $prepend;
  protected $append;

  /**
   * Set the relative path to the prepend file.
   *
   * @param \Grasmash\ComposerScaffold\ScaffoldFilePath $prependPath
   *   The relative path to the prepend file file.
   *
   * @return $this
   */
  public function setPrependFile(ScaffoldFilePath $prependPath) : self {
    $this->prepend = $prependPath;
    return $this;
  }

  /**
   * Get the prepend file.
   *
   * @return \Grasmash\ComposerScaffold\ScaffoldFilePath
   *   The prepend file reference object.
   */
  public function getPrepend() : ScaffoldFilePath {
    return $this->prepend;
  }

  /**
   * Set the relative path to the append file.
   *
   * @param \Grasmash\ComposerScaffold\ScaffoldFilePath $appendPath
   *   The relative path to the append file file.
   *
   * @return $this
   */
  public function setAppendFile(ScaffoldFilePath $appendPath) : self {
    $this->append = $appendPath;
    return $this;
  }

  /**
   * Get the append file.
   *
   * @return \Grasmash\ComposerScaffold\ScaffoldFilePath
   *   The append file reference object.
   */
  public function getAppend() : ScaffoldFilePath {
    return $this->append;
  }

  /**
   * Add interpolation data for our append and prepend source files.
   *
   * @param \Grasmash\ComposerScaffold\Interpolator $interpolator
   *   Interpolator to add data to.
   */
  protected function addInterpolationData(Interpolator $interpolator) {
    if (isset($this->prepend)) {
      $this->prepend->addInterpolationData($interpolator, 'prepend');
    }
    if (isset($this->append)) {
      $this->append->addInterpolationData($interpolator, 'append');
    }
  }

  /**
   * Append or prepend information onto the overridden scaffold file.
   *
   * {@inheritdoc}
   */
  public function process(ScaffoldFilePath $destination, IOInterface $io, array $options) {
    $interpolator = $destination->getInterpolator();
    $this->addInterpolationData($interpolator);
    $destination_path = $destination->fullPath();

    // It is not possible to append / prepend unless the destination path
    // is the same as some scaffold file provided by an earlier package.
    if (!$this->hasOriginalOp()) {
      $io->write($interpolator->interpolate("  - Skip <info>[dest-rel-path]</info>: Cannot append/prepend because no prior package provided a scaffold file at that path."));
      return;
    }

    // First, scaffold the original file. Disable symlinking, because we
    // need a copy of the file if we're going to append / prepend to it.
    @unlink($destination_path);
    $this->originalOp()->process($destination, $io, ['symlink' => FALSE] + $options);

    // Fetch the prepend contents, if provided.
    $prependContents = '';
    if (!empty($this->prepend)) {
      $prependContents = file_get_contents($this->prepend->fullPath()) . "\n";
      $io->write($interpolator->interpolate("  - Prepend to <info>[dest-rel-path]</info> from <info>[prepend-rel-path]</info>"));
    }

    // Fetch the append contents, if provided.
    $appendContents = '';
    if (!empty($this->append)) {
      $appendContents = "\n" . file_get_contents($this->append->fullPath());
      $io->write($interpolator->interpolate("  - Append to <info>[dest-rel-path]</info> from <info>[append-rel-path]</info>"));
    }

    // Exit early if there is no append / prepend data.
    if (empty(trim($prependContents)) && empty(trim($appendContents))) {
      $io->write($interpolator->interpolate("  - Keep <info>[dest-rel-path]</info> unchanged: no content to prepend / append was provided."));
      return;
    }

    // We're going to assume that none of these files are going to be
    // very large, so we will just load them all into memory for now.
    // We'd want to use streaminig if we thought that anyone would scaffold
    // and append very large files.
    $originalContents = file_get_contents($destination_path);

    // Write the appended / prepended contents back to the file.
    $alteredContents = $prependContents . $originalContents . $appendContents;
    file_put_contents($destination_path, $alteredContents);
  }

}
