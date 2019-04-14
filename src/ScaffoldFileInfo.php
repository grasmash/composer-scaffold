<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold;

use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Composer\Composer;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Data file that keeps track of one scaffold file's source, destination, and package.
 */
class ScaffoldFileInfo {

  protected $packageName;
  protected $destinationRelPath;
  protected $destinationFullPath;
  protected $sourceRelPath;
  protected $sourceFullPath;

  /**
   * Set the package name.
   *
   * @param string $packageName
   *   The name of the package this scaffold file info was collected from.
   *
   * @return $this
   */
  public function setPackageName(string $packageName) {
    $this->packageName = $packageName;
    return $this;
  }

  /**
   * Get the package name.
   *
   * @return string
   *   The name of the package this scaffold file info was collected from.
   */
  public function getPackageName() {
    return $this->packageName;
  }

  /**
   * Set the relative path to the destination.
   *
   * @param string $destinationRelPath
   *   The relative path to the destination file.
   *
   * @return $this
   */
  public function setDestinationRelativePath(string $destinationRelPath) {
    $this->destinationRelPath = $destinationRelPath;
    return $this;
  }

  /**
   * Get the relative path to the destination.
   *
   * @return string
   *   The relative path to the destination file.
   */
  public function getDestinationRelativePath() {
    return $this->destinationRelPath;
  }

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
   * Set the full path to the destination.
   *
   * @param string $destinationFullPath
   *   The full path to the destination file.
   *
   * @return $this
   */
  public function setDestinationFullPath(string $destinationFullPath) {
    $this->destinationFullPath = $destinationFullPath;
    return $this;
  }

  /**
   * Get the full path to the destination.
   *
   * @return string
   *   The full path to the destination file.
   */
  public function getDestinationFullPath() {
    return $this->destinationFullPath;
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
   * Determine whether this scaffold file info is for a destination path that was removed.
   *
   * @return bool
   *   True if this scaffold file was removed.
   */
  public function removed() {
    return empty($this->getSourceRelativePath());
  }

  /**
   * Determine if this scaffold file has been overridden by another package.
   *
   * @param string $providing_package
   *   The name of the package that provides the scaffold file at this location,
   *   as returned by self::findProvidingPackage()
   *
   * @return bool
   *   Whether this scaffold file if overridden or removed.
   */
  public function overridden(string $providing_package) {
    return $this->getPackageName() !== $providing_package;
  }

  /**
   * Interpolate a string using the data from this scaffold file info.
   */
  public function interpolate(string $message, array $extra = [], $default = FALSE) {
    $interploator = new Interpolator();

    $data = [
      'package-name' => $this->getPackageName(),
      'dest-rel-path' => $this->getDestinationRelativePath(),
      'src-rel-path' => $this->getSourceRelativePath(),
      'dest-full-path' => $this->getDestinationFullPath(),
      'src-full-path' => $this->getSourceFullPath(),
    ] + $extra;

    return $interploator->interpolate($message, $data, $default);
  }

  /**
   * Moves a single scaffold file from source to destination.
   *
   * @param \Composer\IO\IOInterface $io
   *   The scaffold file to be processed.
   * @param array $options
   *   Assorted operational options, e.g. whether the destination should be a symlink.
   *
   * @throws \Exception
   */
  public function process(IOInterface $io, array $options) {
    $symlink = $options['symlink'];
    $fs = new Filesystem();

    if ($this->removed()) {
      return;
    }

    $destination_path = $this->getDestinationFullPath();
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
      throw new \Exception($this->interpolate("Could not $verb source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!"));
    }
    else {
      $io->write($this->interpolate("  - $verb source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>"));
    }
  }

}
