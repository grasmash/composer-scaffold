<?php

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\Interpolator;
use Grasmash\ComposerScaffold\ScaffoldFilePath;
use Grasmash\ComposerScaffold\ScaffoldOptions;
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
  public function setPrependFile(ScaffoldFilePath $prependPath) {
    $this->prepend = $prependPath;
    return $this;
  }

  /**
   * Set the relative path to the append file.
   *
   * @param \Grasmash\ComposerScaffold\ScaffoldFilePath $appendPath
   *   The relative path to the append file file.
   *
   * @return $this
   */
  public function setAppendFile(ScaffoldFilePath $appendPath) {
    $this->append = $appendPath;
    return $this;
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
  public function process(ScaffoldFilePath $destination, IOInterface $io, ScaffoldOptions $options) {
    $interpolator = $destination->getInterpolator();
    $this->addInterpolationData($interpolator);
    $destination_path = $destination->fullPath();
    // It is not possible to append / prepend unless the destination path
    // is the same as some scaffold file provided by an earlier package.
    if (!$this->hasOriginalOp()) {
      throw new \Exception($interpolator->interpolate("Cannot append/prepend because no prior package provided a scaffold file at that [dest-rel-path]."));
    }
    // First, scaffold the original file. Disable symlinking, because we
    // need a copy of the file if we're going to append / prepend to it.
    @unlink($destination_path);
    $this->originalOp()->process($destination, $io, $options->overrideSymlink(FALSE));
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
    $this->append($destination, $prependContents, $appendContents);
    return (new ScaffoldResult($destination))->setManaged();
  }

  /**
   * Do the actuall append / prepend operation for the provided scaffold file.
   *
   * @param \Grasmash\ComposerScaffold\ScaffoldFilePath $destination
   *   The scaffold file to append / prepend to.
   * @param string $prependContents
   *   The contents to add to the beginning of the file.
   * @param string $appendContents
   *   The contents to add to the end of the file.
   */
  protected function append(ScaffoldFilePath $destination, $prependContents, $appendContents) {
    $interpolator = $destination->getInterpolator();
    $destination_path = $destination->fullPath();
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
