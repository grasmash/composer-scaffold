<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Grasmash\ComposerScaffold\Operations\OperationInterface;
use Grasmash\ComposerScaffold\ScaffoldFilePath;

/**
 * Determine recusively which packages have been allowed to scaffold files.
 *
 * If the root-level composer.json allows drupal/core, and drupal/core allows
 * drupal/assets, then the later package will also implicitly be allowed.
 */
class AllowedPackages {

  /**
   * The Composer service.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  protected $manageOptions;

  /**
   * ManageOptions constructor.
   */
  public function __construct($composer, $manageOptions) {
    $this->composer = $composer;
    $this->manageOptions = $manageOptions;
  }

  /**
   * Called when a newly-added package is discovered to contian scaffolding instructions.
   */
  public function addedPackageWithScaffolding(PackageInterface $package) {
    // @todo remember that we saw the package
  }

  /**
   * Gets a list of all packages that are allowed to copy scaffold files.
   *
   * Configuration for packages specified later will override configuration
   * specified by packages listed earlier. In other words, the last listed
   * package has the highest priority. The root package will always be returned
   * at the end of the list.
   *
   * @return \Composer\Package\PackageInterface[]
   *   An array of allowed Composer packages.
   */
  public function getAllowedPackages(): array {
    $options = $this->manageOptions->getOptions();
    $allowed_packages = $this->recursiveGetAllowedPackages($options->allowedPackages());

    // If the root package defines any file mappings, then implicitly add it
    // to the list of allowed packages. Add it at the end so that it overrides
    // all the preceding packages.
    if ($options->hasFileMapping()) {
      $root_package = $this->composer->getPackage();
      unset($allowed_packages[$root_package->getName()]);
      $allowed_packages[$root_package->getName()] = $root_package;
    }

    // @todo handle any newly-added packages that are not already allowed.

    return $allowed_packages;
  }

  /**
   * Recursivly build a name-to-package mapping from a list of package names.
   *
   * @param string[] $packages_to_allow
   *   List of package names to allow.
   * @param array $allowed_packages
   *   Mapping of package names to PackageInterface of packages already accumulated.
   *
   * @return array
   *   Mapping of package names to PackageInterface in priority order.
   */
  protected function recursiveGetAllowedPackages(array $packages_to_allow, array $allowed_packages = []) {
    foreach ($packages_to_allow as $name) {
      $package = $this->getPackage($name);
      if ($package && $package instanceof PackageInterface && !array_key_exists($name, $allowed_packages)) {
        $allowed_packages[$name] = $package;

        $packageOptions = $this->manageOptions->packageOptions($package);
        $allowed_packages = $this->recursiveGetAllowedPackages($packageOptions->allowedPackages(), $allowed_packages);
      }
    }
    return $allowed_packages;
  }

  /**
   * Retrieve a package from the current composer process.
   *
   * @param string $name
   *   Name of the package to get from the current composer installation.
   *
   * @return \Composer\Package\PackageInterface|null
   *   The Composer package.
   */
  protected function getPackage(string $name) {
    return $this->composer->getRepositoryManager()->getLocalRepository()->findPackage($name, '*');
  }

}
