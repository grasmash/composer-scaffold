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
use Grasmash\ComposerScaffold\Operations\ScaffoldReplaceOp;
use Grasmash\ComposerScaffold\Operations\ScaffoldSkipOp;
use Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface;

/**
 * Core class of the plugin, contains all logic which files should be fetched.
 */
class Handler {

  const PRE_COMPOSER_SCAFFOLD_CMD = 'pre-composer-scaffold-cmd';
  const POST_COMPOSER_SCAFFOLD_CMD = 'post-composer-scaffold-cmd';

  /**
   * The Composer service.
   *
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * Composer's I/O service.
   *
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * Handler constructor.
   *
   * @param \Composer\Composer $composer
   *   The Composer service.
   * @param \Composer\IO\IOInterface $io
   *   The Composer I/O service.
   */
  public function __construct(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  /**
   * Post install command event to execute the scaffolding.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public function onPostCmdEvent(Event $event) {
    $this->scaffold();
  }

  /**
   * Gets the array of file mappings provided by a given package.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The Composer package from which to get the file mappings.
   *
   * @return \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface[]
   *   An array of destination paths => scaffold operation objects.
   */
  public function getPackageFileMappings(PackageInterface $package) : array {
    $package_extra = $package->getExtra();

    if (isset($package_extra['composer-scaffold']['file-mapping'])) {
      $package_file_mappings = $package_extra['composer-scaffold']['file-mapping'];
      return $this->createScaffoldOperations($package, $package_file_mappings);
    }
    else {
      if (!isset($package_extra['composer-scaffold']['allowed-packages'])) {
        $this->io->writeError("The allowed package {$package->getName()} does not provide a file mapping for Composer Scaffold.");
      }
      return [];
    }
  }

  /**
   * Create scaffold operation objects for all items in the file mappings.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param array $package_file_mappings
   *   The package file mappings array (destination path => operation metadata array)
   *
   * @return \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface[]
   *   A list of scaffolding operation objects
   */
  protected function createScaffoldOperations(PackageInterface $package, array $package_file_mappings) {
    $scaffoldOps = [];

    foreach ($package_file_mappings as $key => $value) {
      $scaffoldOps[$key] = $this->createScaffoldOp($package, $value);
    }

    return $scaffoldOps;
  }

  /**
   * Create a scaffolding operation object of an appropriate for the provided metadata.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package that relative paths will be relative from.
   * @param mixed $value
   *   The metadata for this operation object, which varies by operation type.
   *
   * @return \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface
   *   The scaffolding operation object (skip, replace, etc.)
   */
  protected function createScaffoldOp(PackageInterface $package, $value) {
    if (is_bool($value)) {
      if (!$value) {
        return new ScaffoldSkipOp();
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

    // @todo support other operations besides 'replace'.
    if ($value['mode'] != 'replace') {
      throw new \Exception("Unknown scaffold op <comment>{$value['mode']}</comment>.");
    }

    return $this->createScaffoldReplaceOp($package, $value);
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
   *
   * @return \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface
   *   A scaffold replace operation obejct.
   */
  protected function createScaffoldReplaceOp(PackageInterface $package, array $value) {
    $options = $this->getOptions();
    $op = new ScaffoldReplaceOp();

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

  /**
   * Copies all scaffold files from source to destination.
   */
  public function scaffold() {
    // Call any pre-scaffold scripts that may be defined.
    $dispatcher = new EventDispatcher($this->composer, $this->io);
    $dispatcher->dispatch(self::PRE_COMPOSER_SCAFFOLD_CMD);

    $locationReplacements = $this->getLocationReplacements();

    // Get the list of allowed packages, and then use it to recursively
    // to fetch the list of file mappings, and normalize them.
    $allowedPackages = $this->getAllowedPackages();
    $file_mappings = $this->getFileMappingsFromPackages($allowedPackages);

    // Collect the list of file mappings, and determine which take priority.
    $scaffoldCollection = new ScaffoldCollection();
    $scaffoldCollection->coalateScaffoldFiles($file_mappings, $locationReplacements);

    // Write the collected scaffold files to the designated location on disk.
    $this->scaffoldPackageFiles($scaffoldCollection);

    // Generate an autoload file in the document root that includes
    // the autoload.php file in the vendor directory, wherever that is.
    // Drupal requires this in order to easily locate relocated vendor dirs.
    $this->generateAutoload();

    // Call post-scaffold scripts.
    $dispatcher->dispatch(self::POST_COMPOSER_SCAFFOLD_CMD);
  }

  /**
   * Generate the autoload file at the project root.
   *
   * Include the autoload file that Composer generated.
   */
  public function generateAutoload() {
    $vendorPath = $this->getVendorPath();
    $webroot = $this->getWebRoot();

    // Calculate the relative path from the webroot (location of the project
    // autoload.php) to the vendor directory.
    $fs = new SymfonyFilesystem();
    $relativeVendorPath = $fs->makePathRelative($vendorPath, realpath($webroot));

    $fs->dumpFile($webroot . "/autoload.php", $this->autoLoadContents($relativeVendorPath));
  }

  /**
   * Build the contents of the autoload file.
   *
   * @return string
   *   Return the contents for the autoload.php.
   */
  protected function autoLoadContents(string $relativeVendorPath) : string {
    $relativeVendorPath = rtrim($relativeVendorPath, '/');

    return <<<EOF
<?php

/**
 * @file
 * Includes the autoloader created by Composer.
 *
 * This file was generated by composer-scaffold.
 *.
 * @see composer.json
 * @see index.php
 * @see core/install.php
 * @see core/rebuild.php
 * @see core/modules/statistics/statistics.php
 */

return require __DIR__ . '/$relativeVendorPath/autoload.php';

EOF;
  }

  /**
   * Get the path to the 'vendor' directory.
   *
   * @return string
   *   The file path of the vendor directory.
   */
  public function getVendorPath() {
    $vendorDir = $this->composer->getConfig()->get('vendor-dir');
    $filesystem = new Filesystem();
    $filesystem->ensureDirectoryExists($vendorDir);
    return $filesystem->normalizePath(realpath($vendorDir));
  }

  /**
   * Retrieve the path to the web root.
   *
   * @return string
   *   The file path of the web root.
   *
   * @throws \Exception
   */
  public function getWebRoot() {
    $options = $this->getOptions();
    // @todo Allow packages to set web root location?
    if (empty($options['locations']['web-root'])) {
      throw new \Exception("The extra.composer-scaffold.location.web-root is not set in composer.json.");
    }
    return $options['locations']['web-root'];
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
    $package = $this->composer->getRepositoryManager()->getLocalRepository()->findPackage($name, '*');
    if (is_null($package)) {
      throw new \Exception("<comment>Composer Scaffold could not find installed package `$name`.</comment>");
    }

    return $package;
  }

  /**
   * Retrieve options from optional "extra" configuration.
   *
   * @return array
   *   The composer-scaffold configuration array.
   */
  protected function getOptions() : array {
    return $this->getOptionsForPackage($this->composer->getPackage());
  }

  /**
   * Retrieve options from optional "extra" configuration for a package.
   *
   * @param \Composer\Package\PackageInterface $package
   *   The package to pull configuration options from.
   *
   * @return array
   *   The composer-scaffold configuration array for the given package.
   */
  protected function getOptionsForPackage(PackageInterface $package) : array {
    $extra = $package->getExtra() + ['composer-scaffold' => []];

    return $extra['composer-scaffold'] + [
      "allowed-packages" => [],
      "locations" => [],
      "symlink" => FALSE,
      "file-mapping" => [],
    ];
  }

  /**
   * GetLocationReplacements creates an interpolator for the 'locations' element.
   *
   * The interpolator returned will replace a path string with the tokens
   * defined in the 'locations' element.
   *
   * @return Interpolator
   *   Object that will do replacements in a string using tokens in 'locations' element.
   */
  public function getLocationReplacements() {
    $interpolator = new Interpolator();

    $fs = new Filesystem();
    $options = $this->getOptions();
    $locations = $options['locations'] + ['web_root' => './'];
    $locations = array_map(
      function ($location) use ($fs) {
        $fs->ensureDirectoryExists($location);
        $location = realpath($location);
        return $location;
      },
      $locations
    );

    return $interpolator->setData($locations);
  }

  /**
   * Gets a consolidated list of file mappings from all allowed packages.
   *
   * @param \Composer\Package\Package[] $allowed_packages
   *   A multidimensional array of file mappings, as returned by
   *   self::getAllowedPackages().
   *
   * @return \Grasmash\ComposerScaffold\Operations\ScaffoldOperationInterface[]
   *   An array of destination paths => scaffold operation objects.
   */
  protected function getFileMappingsFromPackages(array $allowed_packages) : array {
    $file_mappings = [];
    foreach ($allowed_packages as $package_name => $package) {
      $package_file_mappings = $this->getPackageFileMappings($package);
      $file_mappings[$package_name] = $package_file_mappings;
    }
    return $file_mappings;
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
  protected function getAllowedPackages(): array {
    $options = $this->getOptions() + [
      'allowed-packages' => [],
    ];
    $allowed_packages = $this->recursiveGetAllowedPackages($options['allowed-packages']);

    // Add root package at the end so that it overrides all the preceding
    // package.
    $root_package = $this->composer->getPackage();
    $allowed_packages[$root_package->getName()] = $root_package;

    return $allowed_packages;
  }

  /**
   * Description.
   *
   * @param string[] $packages_to_allow
   *   List of package names.
   * @param array $allowed_packages
   *   Mapping of package names to PackageInterface.
   *
   * @return array
   *   Mapping of package names to PackageInterface in priority order.
   */
  protected function recursiveGetAllowedPackages(array $packages_to_allow, array $allowed_packages = []) {
    $root_package = $this->composer->getPackage();
    foreach ($packages_to_allow as $name) {
      if ($root_package->getName() === $name) {
        continue;
      }
      $package = $this->getPackage($name);
      if ($package instanceof PackageInterface && !array_key_exists($name, $allowed_packages)) {
        $allowed_packages[$name] = $package;

        $packageOptions = $this->getOptionsForPackage($package);
        $allowed_packages = $this->recursiveGetAllowedPackages($packageOptions['allowed-packages'], $allowed_packages);
      }
    }
    return $allowed_packages;
  }

  /**
   * Scaffolds the files in our scaffold collection, package-by-package.
   *
   * @param ScaffoldCollection $scaffoldCollection
   *   The scaffold files to process.
   */
  protected function scaffoldPackageFiles(ScaffoldCollection $scaffoldCollection) {
    $options = $this->getOptions();

    // We could simply scaffold all of the files from $list_of_scaffold_files,
    // which contain only the list of files to be processed. We iterate over
    // $resolved_file_mappings instead so that we can print out all of the
    // scaffold files grouped by the package that provided them, including
    // those not being scaffolded (because they were overridden or removed
    // by some later package).
    foreach ($scaffoldCollection->fileMappings() as $package_name => $package_scaffold_files) {
      $this->io->write("Scaffolding files for <comment>$package_name</comment>:");
      foreach ($package_scaffold_files as $dest_rel_path => $scaffold_file) {
        $overriding_package = $scaffoldCollection->findProvidingPackage($scaffold_file);
        if ($scaffold_file->overridden($overriding_package)) {
          $this->io->write($scaffold_file->interpolate("  - <info>[dest-rel-path]</info> overridden in <comment>$overriding_package</comment>"));
        }
        else {
          $scaffold_file->process($this->io, $options);
        }
      }
    }
  }

}
