<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Scaffold operation to add to the beginning and/or end of a scaffold file.
 */
class AppendPrependOp implements OperationInterface, OriginalOpAwareInterface {

  use OriginalOpAwareTrait;

  protected $prepend;
  protected $append;

  /**
   * Set the relative path to the prepend file.
   *
   * @param ScaffoldSourcePath $prependPath
   *   The relative path to the prepend file file.
   *
   * @return $this
   */
  public function setPrependFile(ScaffoldSourcePath $prependPath) {
    $this->prepend = $prependPath;
    return $this;
  }

  /**
   * Get the prepend file.
   *
   * @return ScaffoldSourcePath
   *   The prepend file reference object.
   */
  public function getPrepend() {
    return $this->prepend;
  }

  /**
   * Set the relative path to the append file.
   *
   * @param ScaffoldSourcePath $appendPath
   *   The relative path to the append file file.
   *
   * @return $this
   */
  public function setAppendFile(ScaffoldSourcePath $appendPath) {
    $this->append = $appendPath;
    return $this;
  }

  /**
   * Get the append file.
   *
   * @return ScaffoldSourcePath
   *   The append file reference object.
   */
  public function getAppend() {
    return $this->append;
  }

  /**
   * Interpolate a string using the data from this scaffold file info.
   */
  public function interpolationData() {
    return [
      'prepend-rel-path' => $this->getPrepend()->relativePath(),
      'prepend-full-path' => $this->getPrepend()->fullPath(),
      'append-rel-path' => $this->getAppend()->relativePath(),
      'append-full-path' => $this->getAppend()->fullPath(),
    ];
    return $data;
  }

  /**
   * Process the replace operation. This could be a copy or a symlink.
   */
  public function process(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    // It is not possible to append / prepend unless the destination path
    // is the same as some scaffold file provided by an earlier package.
    if (!$this->hasOriginalOp()) {
      $io->write($interpolator->interpolate("  - Skip appending / prepending to scaffold file <info>[dest-rel-path]</info> because no prior package provided a scaffold file at that path."));
      return;
    }

    // First, scaffold the original file.
    $this->originalOp()->process($scaffold_file, $io, $options);

    // Fetch the prepend contents, if provided.
    $prependContents = '';
    if (isset($this->prepend)) {
      $prependContents = file_get_contents($this->prepend->fullPath()) . "\n";
    }

    // Fetch the append contents, if provided.
    $appendContents = '';
    if (isset($this->append)) {
      $appendContents = "\n" . file_get_contents($this->append->fullPath());
    }

    // Exit early if there is no append / prepend information.
    if (empty(trim($prependContents)) && empty(trim($appendContents))) {
      $io->write($interpolator->interpolate("  - Skip appending / prepending to scaffold file <info>[dest-rel-path]</info> because no content to prepend / append was provided."));
      return;
    }

    // We're going to assume that none of these files are going to be
    // very large, so we will just load them all into memory for now.
    // We'd want to use streaminig if we thought that anyone would scaffold
    // and append very large files.
    $destination_path = $scaffold_file->getDestinationFullPath();
    $originalContents = file_get_contents($destination_path);

    // Write the appended / prepended contents back to the file.
    $alteredContents = $prependContents . $originalContents . $appendContents;
    file_put_contents($destination_path, $alteredContents);
  }

}
