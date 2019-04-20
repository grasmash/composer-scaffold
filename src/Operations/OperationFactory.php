<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;

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
  public function normalizeScaffoldMetadata(string $key, $value) : array {
    if (is_bool($value)) {
      if (!$value) {
        return ['mode' => 'skip'];
      }
      throw new \Exception("File mapping $key cannot be given the value 'true'.");
    }
    if (empty($value)) {
      throw new \Exception("File mapping $key cannot be empty.");
    }
    if (is_string($value)) {
      $value = ['path' => $value];
    }
    // If there is no 'mode', but there is an 'append-path' or a 'prepend-path',
    // then the mode is 'append-prepend'.
    if (
      !isset($value['mode']) &&
      (isset($value['append-path']) || isset($value['prepend-path']))
    ) {
      $value['mode'] = 'append-prepend';
    }
    // If there is no 'mode', then the default is 'replace'.
    if (!isset($value['mode'])) {
      $value['mode'] = 'replace';
    }
    // If the mode is 'append' or 'prepend', then allow 'path' as
    // a shortcut for 'append-path' / 'prepend-path'.
    if (isset($value['path']) && ($value['mode'] == 'append') || ($value['mode'] == 'prepend')) {
      $value[$value['mode'] . '-path'] = $value['path'];
      unset($value['path']);
    }
    // Accept 'true' or 'always' for the 'overwrite' property. Any other value
    // is treated as 'false'.
    if (isset($value['overwrite'])) {
      $value['overwrite'] = $this->normalizeOverwrite($value['overwrite']);
    }
    return $value;
  }

  /**
   * Convert overwrite value from one of the allowed values to a boolean.
   *
   * Any value not recognized as 'true' is interpreted as 'false'.
   *
   * @param mixed $overwrite
   *   Overwrite value as specified by user.
   *
   * @return bool
   *   Normalized overwrite value expressed as a boolean.
   */
  protected function normalizeOverwrite($overwrite) : bool {
    return ($overwrite === TRUE) ||
      ($overwrite == 'true') ||
      ($overwrite == 'always');
  }

  /**
   * Determine whether we should default to 'overwrite' true, or 'overwrite' false.
   *
   * @param array $options
   *   Global settings from extras.composer-scaffold.
   *
   * @return bool
   *   The default value for the overwrite option.
   */
  protected function getDefaultOverwrite(array $options) : bool {
    if (isset($options['overwrite'])) {
      return $this->normalizeOverwrite($options['overwrite']);
    }

    return TRUE;
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
   * @param array $options
   *   Configuration options from the top-level composer.json file.
   *
   * @return \Grasmash\ComposerScaffold\Operations\OperationInterface
   *   The scaffolding operation object (skip, replace, etc.)
   */
  public function createScaffoldOp(PackageInterface $package, $dest_rel_path, $value, array $options) : OperationInterface {
    switch ($value['mode']) {
      case 'skip':
        return new SkipOp();

      case 'replace':
        return $this->createReplaceOp($package, $dest_rel_path, $value, $options);

      case 'append':
      case 'prepend':
      case 'prepend-append':
      case 'append-prepend':
        return $this->createAppendOp($package, $dest_rel_path, $value, $options);
    }

    // @todo support other operations besides 'replace'.
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
   * @param array $options
   *   Configuration options from the top-level composer.json file.
   *
   * @return \Grasmash\ComposerScaffold\Operations\OperationInterface
   *   A scaffold replace operation obejct.
   */
  protected function createReplaceOp(PackageInterface $package, string $dest_rel_path, array $metadata, array $options) : OperationInterface {
    $op = new ReplaceOp();

    // If this op does not provide an 'overwrite' value, then fill in the default.
    $metadata += ['overwrite' => $this->getDefaultOverwrite($options)];

    if (empty($metadata['path'])) {
      throw new \Exception("'path' component required for 'replace' operations.");
    }

    $package_name = $package->getName();
    $package_path = $this->getPackagePath($package);

    $source = ScaffoldSourcePath::create($package_name, $package_path, $dest_rel_path, $metadata['path']);

    $op
      ->setSource($source)
      ->setOverwrite($metadata['overwrite']);

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
   * @param array $options
   *   Configuration options from the top-level composer.json file.
   *
   * @return \Grasmash\ComposerScaffold\Operations\OperationInterface
   *   A scaffold replace operation obejct.
   */
  protected function createAppendOp(PackageInterface $package, string $dest_rel_path, array $metadata, array $options) : OperationInterface {

    $op = new AppendPrependOp();

    $package_name = $package->getName();
    $package_path = $this->getPackagePath($package);

    if (isset($metadata['prepend-path'])) {
      $prepend_source_file = ScaffoldSourcePath::create($package_name, $package_path, $dest_rel_path, $metadata['prepend-path']);
      $op->setPrependFile($prepend_source_file);
    }

    if (isset($metadata['append-path'])) {
      $append_source_file = ScaffoldSourcePath::create($package_name, $package_path, $dest_rel_path, $metadata['append-path']);
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
  protected function getPackagePath(PackageInterface $package) : string {
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
