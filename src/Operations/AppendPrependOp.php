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
abstract class AppendPrependOp implements OperationInterface {

  protected $prependRelPath;
  protected $prependFullPath;
  protected $appendRelPath;
  protected $appendFullPath;
  protected $originalScaffoldOp;

  /**
   * Set the relative path to the source.
   *
   * @param string $sourceRelPath
   *   The relative path to the source file.
   *
   * @return $this
   */
  public function setSourceRelativePath(string $sourceRelPath) {
    $this->sourceRelPath = $sourceRelPath;
    return $this;
  }

  /**
   * Get the relative path to the source.
   *
   * @return string
   *   The relative path to the source file.
   */
  public function getSourceRelativePath() {
    return $this->sourceRelPath;
  }

  /**
   * Set the full path to the source.
   *
   * @param string $sourceFullPath
   *   The full path to the source file.
   *
   * @return $this
   */
  public function setSourceFullPath(string $sourceFullPath) {
    $this->sourceFullPath = $sourceFullPath;
    return $this;
  }

  /**
   * Get the full path to the source.
   *
   * @return string
   *   The full path to the source file.
   */
  public function getSourceFullPath() {
    return $this->sourceFullPath;
  }

  /**
   * Set whether the scaffold file should overwrite existing files at the same path.
   *
   * @param bool $overwrite
   *   Whether to overwrite existing files.
   *
   * @return $this
   */
  public function setOverwrite(bool $overwrite) {
    $this->overwrite = $overwrite;
    return $this;
  }

  /**
   * Determine whether scaffold file should overwrite files already at the same path.
   *
   * @return bool
   *   Value of the 'overwrite' option.
   */
  public function getOverwrite() {
    return $this->overwrite;
  }

  /**
   * Interpolate a string using the data from this scaffold file info.
   */
  public function interpolationData() {
    return [
      'src-rel-path' => $this->getSourceRelativePath(),
      'src-full-path' => $this->getSourceFullPath(),
    ];
    return $data;
  }

  /**
   * Process the replace operation. This could be a copy or a symlink.
   */
  public function process(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    $this->originalScaffoldOp->process($scaffold_file, $io, $options);

    $fs = new Filesystem();

    $destination_path = $scaffold_file->getDestinationFullPath();

    // Do nothing if overwrite is 'false' and a file already exists at the destination.
    if (($this->getOverwrite() === FALSE) && file_exists($destination_path)) {
      $interpolator = $scaffold_file->getInterpolator();
      $io->write($interpolator->interpolate("  - Skip scaffold file <info>[dest-rel-path]</info> because it already exists."));
      return;
    }

    // Get rid of the destination if it exists, and make sure that
    // the directory where it's going to be placed exists.
    @unlink($destination_path);
    $fs->ensureDirectoryExists(dirname($destination_path));

    $this->placeScaffold($scaffold_file, $io, $options);
  }

  /**
   * Place either a symlink or copy the scaffold file as appropriate.
   */
  abstract public function placeScaffold(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options);

}
