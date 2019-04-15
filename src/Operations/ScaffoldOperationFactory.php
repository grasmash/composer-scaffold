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
class ScaffoldOperationFactory {

  protected $composer;

  /**
   * ScaffoldOperationFactory constructor.
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
   * Create a scaffolding operation object of an appropriate for the provided metadata.
   *
   * @param string $key
   *   The key (destination path) for the value to normalize.
   * @param mixed $value
   *   The metadata for this operation object, which varies by operation type.
   *
   * @return array
   *   Normalized scaffold metadata.
   */
  public function normalizeScaffoldMetadata(string $key, $value) {
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
      $value = [
        'mode' => 'replace',
        'path' => $value,
      ];
    }
    return $value;
  }

  /**
   * Create a scaffolding operation object of an appropriate for the provided metadata.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param mixed $value
   *   The metadata for this operation object, which varies by operation type.
   * @param array $options
   *   Configuration options from the top-level composer.json file.
   *
   * @return \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface
   *   The scaffolding operation object (skip, replace, etc.)
   */
  public function createScaffoldOp(PackageInterface $package, $value, array $options) {
    switch ($value['mode']) {
      case 'skip':
        return new ScaffoldSkipOp();

      case 'replace':
        return $this->createScaffoldReplaceOp($package, $value, $options);
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
   * @param array $value
   *   The metadata for this operation object, i.e. the relative 'path'.
   * @param array $options
   *   Configuration options from the top-level composer.json file.
   *
   * @return \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface
   *   A scaffold replace operation obejct.
   */
  protected function createScaffoldReplaceOp(PackageInterface $package, array $value, array $options) {
    $op = $options['symlink'] ?
      new ScaffoldSymlinkOp() :
      new ScaffoldCopyOp();

    $source_rel_path = $value['path'];
    $src_full_path = $this->resolveSourceLocation($package, $source_rel_path);

    $op
      ->setSourceRelativePath($source_rel_path)
      ->setSourceFullPath($src_full_path);

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

  /**
   * ResolveSourceLocation converts the relative source path into an absolute path.
   *
   * The path returned will be relative to the package installation location.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package containing the source file.
   * @param string $source
   *   Source location provided as a relative path.
   *
   * @return string
   *   Source location converted to an absolute path, or empty if removed.
   */
  public function resolveSourceLocation(PackageInterface $package, string $source) {
    if (empty($source)) {
      return '';
    }

    $package_path = $this->getPackagePath($package);
    $package_name = $package->getName();

    $source_path = $package_path . '/' . $source;

    if (!file_exists($source_path)) {
      throw new \Exception("Scaffold file <info>$source</info> not found in package <comment>$package_name</comment>.");
    }
    if (is_dir($source_path)) {
      throw new \Exception("Scaffold file <info>$source</info> in package <comment>$package_name</comment> is a directory; only files may be scaffolded.");
    }

    return $source_path;
  }

}
