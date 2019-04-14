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
 * Scaffold operation to copy or symlink from source to destination.
 */
class ScaffoldReplaceOp implements ScaffoldOperationInterface {

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
    $interpolator = $scaffold_file->getInterpolator();
    $symlink = $options['symlink'];
    $fs = new Filesystem();

    $destination_path = $scaffold_file->getDestinationFullPath();
    $source_path = $this->getSourceFullPath();

    // Get rid of the destination if it exists, and make sure that
    // the directory where it's going to be placed exists.
    @unlink($destination_path);
    $fs->ensureDirectoryExists(dirname($destination_path));
    $success = FALSE;
    if ($symlink) {
      try {
        $success = $fs->relativeSymlink($source_path, $destination_path);
      }
      catch (\Exception $e) {
      }
    }
    else {
      $success = copy($source_path, $destination_path);
    }
    $verb = $symlink ? 'symlink' : 'copy';
    if (!$success) {
      throw new \Exception($interpolator->interpolate("Could not $verb source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!", $this->interpolationData()));
    }
    else {
      $io->write($interpolator->interpolate("  - $verb source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>", $this->interpolationData()));
    }
  }

}
