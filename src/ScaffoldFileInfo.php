<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\Operations\OperationInterface;
use Grasmash\ComposerScaffold\ScaffoldFilePath;

/**
 * Data object that keeps track of one scaffold file.
 *
 * Scafold files are identified primariy by their destination path. Each
 * scaffold file also has an 'operation' object that controls how the
 * scaffold file will be placed (e.g. via copy or symlink, or maybe by
 * appending multiple files together).  The operation may have one or more
 * source files.
 */
class ScaffoldFileInfo {

  protected $destination;
  protected $op;

  /**
   * Set the Scaffold operation.
   *
   * @param \Grasmash\ComposerScaffold\Operations\OperationInterface $op
   *   Operations object that will handle scaffolding operations.
   *
   * @return $this
   */
  public function setOp(OperationInterface $op) : self {
    $this->op = $op;
    return $this;
  }

  /**
   * Get the Scaffold operation.
   *
   * @return \Grasmash\ComposerScaffold\Operations\OperationInterface
   *   Operations object that handles scaffolding (copy, make symlink, etc).
   */
  public function op() : OperationInterface {
    return $this->op;
  }

  /**
   * Get the package name.
   *
   * @return string
   *   The name of the package this scaffold file info was collected from.
   */
  public function packageName() : string {
    return $this->destination->packageName();
  }

  /**
   * Set the relative path to the destination.
   *
   * @param \Grasmash\ComposerScaffold\Operations\ScaffoldFilePath $destination
   *   The full and relative paths to the destination file and the package defining it.
   *
   * @return $this
   */
  public function setDestination(ScaffoldFilePath $destination) : self {
    $this->destination = $destination;
    return $this;
  }

  /**
   * Get the destination.
   *
   * @return \Grasmash\ComposerScaffold\ScaffoldFilePath
   *   The scaffold path to the destination file.
   */
  public function destination() : ScaffoldFilePath {
    return $this->destination;
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
  public function overridden(string $providing_package) : bool {
    return $this->packageName() !== $providing_package;
  }

  /**
   * Interpolate a string using the data from this scaffold file info.
   *
   * @return Interpolator
   *   An interpolator for making string replacements.
   */
  public function getInterpolator() : Interpolator {
    return $this->destination->getInterpolator();
  }

  /**
   * Given a message with placeholders, return the interpolated result.
   *
   * @param string $message
   *   Message with placeholders to fill in.
   * @param array $extra
   *   Additional data to merge with the interpolator.
   * @param mixed $default
   *   Default value to use for missing placeholders, or FALSE to keep them.
   *
   * @return string
   *   Interpolated string with placeholders replaced.
   */
  public function interpolate(string $message, array $extra = [], $default = FALSE) : string {
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
    return $this->op()->process($this->destination, $io, $options);
  }

}
