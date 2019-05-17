<?php

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;
use Grasmash\ComposerScaffold\ScaffoldFilePath;
use Grasmash\ComposerScaffold\ScaffoldOptions;

/**
 * Create Scaffold operation objects based on provided metadata.
 */
class OperationFactory {
  protected $composer;

  /**
   * OperationFactory constructor.
   *
   * @param \Composer\Composer $composer
   *   Reference to the 'Composer' object, since the Scaffold Operation Factory
   *   is also responsible for evaluating relative package paths as it creates
   *   scaffold operations.
   */
  public function __construct(Composer $composer) {
    $this->composer = $composer;
  }

  /**
   * Normalize metadata, converting literal values into arrays with the same meaning.
   *
   * Conversions performed include:
   *   - Boolean 'false' means "skip".
   *   - A string menas "replace", with the string value becoming the path.
   *
   * @param string $key
   *   The key (destination path) for the value to normalize.
   * @param mixed $value
   *   The metadata for this operation object, which varies by operation type.
   *
   * @return array
   *   Normalized scaffold metadata.
   */
  public function normalizeScaffoldMetadata($key, $value) {
    if (is_bool($value)) {
      if (!$value) {
        return ['mode' => 'skip'];
      }
      throw new \Exception("File mapping {$key} cannot be given the value 'true'.");
    }
    if (empty($value)) {
      throw new \Exception("File mapping {$key} cannot be empty.");
    }
    if (is_string($value)) {
      $value = ['path' => $value];
    }
    // If there is no 'mode', but there is an 'append' or a 'prepend' path,
    // then the mode is 'append' (append + prepend).
    if (!isset($value['mode']) && (isset($value['append']) || isset($value['prepend']))) {
      $value['mode'] = 'append';
    }
    // If there is no 'mode', then the default is 'replace'.
    if (!isset($value['mode'])) {
      $value['mode'] = 'replace';
    }
    return $value;
  }

  /**
   * Create a scaffolding operation object of an appropriate for the provided metadata.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param string $dest_rel_path
   *   The destination path for the scaffold file. Used only for error messages.
   * @param mixed $value
   *   The metadata for this operation object, which varies by operation type.
   * @param \Grasmash\ComposerScaffold\ScaffoldOptions $options
   *   Configuration options from the top-level composer.json file.
   *
   * @return \Grasmash\ComposerScaffold\Operations\OperationInterface
   *   The scaffolding operation object (skip, replace, etc.)
   */
  public function createScaffoldOp(PackageInterface $package, $dest_rel_path, $value, ScaffoldOptions $options) {
    switch ($value['mode']) {
      case 'skip':
        return new SkipOp();

      case 'replace':
        return $this->createReplaceOp($package, $dest_rel_path, $value, $options);

      case 'append':
        return $this->createAppendOp($package, $dest_rel_path, $value, $options);
    }
    throw new \Exception("Unknown scaffold opperation mode <comment>{$value['mode']}</comment>.");
  }

  /**
   * Create a 'replace' scaffold op.
   *
   * Replace ops may copy or symlink, depending on settings.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param string $dest_rel_path
   *   The destination path for the scaffold file. Used only for error messages.
   * @param array $metadata
   *   The metadata for this operation object, i.e. the relative 'path'.
   * @param \Grasmash\ComposerScaffold\ScaffoldOptions $options
   *   Configuration options from the top-level composer.json file.
   *
   * @return \Grasmash\ComposerScaffold\Operations\OperationInterface
   *   A scaffold replace operation obejct.
   */
  protected function createReplaceOp(PackageInterface $package, $dest_rel_path, array $metadata, ScaffoldOptions $options) {
    $op = new ReplaceOp();
    // If this op does not provide an 'overwrite' value, then fill in the default.
    $metadata += ['overwrite' => $options->overwrite()];
    if (!isset($metadata['path'])) {
      throw new \Exception("'path' component required for 'replace' operations.");
    }
    $package_name = $package->getName();
    $package_path = $this->getPackagePath($package);
    $source = ScaffoldFilePath::sourcePath($package_name, $package_path, $dest_rel_path, $metadata['path']);
    $op->setSource($source)->setOverwrite($metadata['overwrite']);
    return $op;
  }

  /**
   * Create an 'append' (or 'prepend') scaffold op.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param string $dest_rel_path
   *   The destination path for the scaffold file. Used only for error messages.
   * @param array $metadata
   *   The metadata for this operation object, i.e. the relative 'path'.
   * @param \Grasmash\ComposerScaffold\ScaffoldOptions $options
   *   Configuration options from the top-level composer.json file.
   *
   * @return \Grasmash\ComposerScaffold\Operations\OperationInterface
   *   A scaffold replace operation obejct.
   */
  protected function createAppendOp(PackageInterface $package, $dest_rel_path, array $metadata, ScaffoldOptions $options) {
    $op = new AppendOp();
    $package_name = $package->getName();
    $package_path = $this->getPackagePath($package);
    if (isset($metadata['prepend'])) {
      $prepend_source_file = ScaffoldFilePath::sourcePath($package_name, $package_path, $dest_rel_path, $metadata['prepend']);
      $op->setPrependFile($prepend_source_file);
    }
    if (isset($metadata['append'])) {
      $append_source_file = ScaffoldFilePath::sourcePath($package_name, $package_path, $dest_rel_path, $metadata['append']);
      $op->setAppendFile($append_source_file);
    }
    return $op;
  }

  /**
   * Gets the file path of a package.
   *
   * Note that if we call getInstallPath on the root package, we get the
   * wrong answer (the installation manager thinks our package is in
   * vendor). We therefore add special checking for this case.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package.
   *
   * @return string
   *   The file path.
   */
  protected function getPackagePath(PackageInterface $package) {
    if ($package->getName() == $this->composer->getPackage()->getName()) {
      // This will respect the --working-dir option if Composer is invoked with
      // it. There is no API or method to determine the filesystem path of
      // a package's composer.json file.
      return getcwd();
    }
    else {
      return $this->composer->getInstallationManager()->getInstallPath($package);
    }
  }

}
