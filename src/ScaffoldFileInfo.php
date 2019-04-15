<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface;

/**
 * Data file that keeps track of one scaffold file's source, destination, and package.
 */
class ScaffoldFileInfo {

  protected $packageName;
  protected $destinationRelPath;
  protected $destinationFullPath;
  protected $op;

  /**
   * Set the Scaffold operation.
   *
   * @param \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface $op
   *   Operations object that will handle scaffolding operations.
   *
   * @return $this
   */
  public function setOp(ScaffoldOperationInterface $op) {
    $this->op = $op;
    return $this;
  }

  /**
   * Get the Scaffold operation.
   *
   * @return \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface
   *   Operations object that handles scaffolding (copy, make symlink, etc).
   */
  public function op() {
    return $this->op;
  }

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
  public function getInterpolator() {
    $interpolator = new Interpolator();

    $data = [
      'package-name' => $this->getPackageName(),
      'dest-rel-path' => $this->getDestinationRelativePath(),
      'dest-full-path' => $this->getDestinationFullPath(),
    ];

    $interpolator->setData($data);
    return $interpolator;
  }

  /**
   * Interpolate a string using the data from this scaffold file info.
   */
  public function interpolate(string $message, array $extra = [], $default = FALSE) {
    $interpolator = $this->getInterpolator();
    return $interpolator->interpolate($message, $extra, $default);
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
    $this->op()->process($this, $io, $options);
  }

}
