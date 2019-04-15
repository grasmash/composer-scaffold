<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Scaffold operation to copy or symlink from source to destination.
 */
abstract class ScaffoldReplaceOp implements ScaffoldOperationInterface {

  protected $sourceRelPath;
  protected $sourceFullPath;

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
    $fs = new Filesystem();

    $destination_path = $scaffold_file->getDestinationFullPath();

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
