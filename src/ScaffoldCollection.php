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
 * ScaffoldCollection keeps track of the collection of files to be scaffolded.
 */
class ScaffoldCollection {

  protected $composer;
  protected $listOfScaffoldFiles;
  protected $resolvedFileMappings;

  /**
   * ScaffoldCollection constructor.
   */
  public function __construct($composer) {
    $this->composer = $composer;
  }

  /**
   * Fetch the file mappings.
   *
   * @return array
   *   Associative array containing package name => file mappings
   */
  public function fileMappings() {
    return $this->resolvedFileMappings;
  }

  /**
   * Gets the file path of a package.
   *
   * @param string $package_name
   *   The package name.
   *
   * @return string
   *   The file path.
   */
  protected function getPackagePath(string $package_name) : string {
    if ($package_name == $this->composer->getPackage()->getName()) {
      // This will respect the --working-dir option if Composer is invoked with
      // it. There is no API or method to determine the filesystem path of
      // a package's composer.json file.
      return getcwd();
    }
    else {
      $package = $this->composer->getRepositoryManager()->getLocalRepository()->findPackage($package_name, '*');
      return $this->composer->getInstallationManager()->getInstallPath($package);
    }
  }

  /**
   * Return the package name that provides the scaffold file info at this destination path.
   *
   * Given the list of all scaffold file info objects, return the package that
   * provides the scaffold file info for the scaffold file that will be placed
   * at the destination that this scaffold file would be placed at. Note that
   * this will be the same as $this->getPackageName() unless this scaffold file
   * has been overridden or removed by some other package.
   *
   * @param ScaffoldFileInfo $scaffold_file
   *   The scaffold file to use to find a providing package name.
   *
   * @return string
   *   The name of the package that provided the scaffold file information.
   */
  public function findProvidingPackage(ScaffoldFileInfo $scaffold_file) {
    // The scaffold file should always be in our list, but we will check
    // just to be sure that it really is.
    if (!array_key_exists($scaffold_file->getDestinationRelativePath(), $this->listOfScaffoldFiles)) {
      throw new \Exception("Scaffold file not found in list of all scaffold files.");
    }
    $overridden_scaffold_file = $this->listOfScaffoldFiles[$scaffold_file->getDestinationRelativePath()];
    return $overridden_scaffold_file->getPackageName();
  }

  /**
   * Copy all files, as defined by $file_mappings.
   *
   * @param array $file_mappings
   *   An multidimensional array of file mappings, as returned by
   *   self::getFileMappingsFromPackages().
   * @param Interpolator $locationReplacements
   *   An object with the location mappings (e.g. [web-root]).
   */
  public function coalateScaffoldFiles(array $file_mappings, Interpolator $locationReplacements) {
    $resolved_file_mappings = [];
    $resolved_package_file_list = [];
    foreach ($file_mappings as $package_name => $package_file_mappings) {
      $package_path = $this->getPackagePath($package_name);
      foreach ($package_file_mappings as $destination_rel_path => $source_rel_path) {
        $src_full_path = $this->resolveSourceLocation($package_name, $package_path, $source_rel_path);
        $dest_full_path = $locationReplacements->interpolate($destination_rel_path);

        $scaffold_file = (new ScaffoldFileInfo())
          ->setPackageName($package_name)
          ->setDestinationRelativePath($destination_rel_path)
          ->setSourceRelativePath($source_rel_path)
          ->setDestinationFullPath($dest_full_path)
          ->setSourceFullPath($src_full_path);

        $list_of_scaffold_files[$destination_rel_path] = $scaffold_file;
        $resolved_file_mappings[$package_name][$destination_rel_path] = $scaffold_file;
      }
    }
    $this->listOfScaffoldFiles = $list_of_scaffold_files;
    $this->resolvedFileMappings = $resolved_file_mappings;
  }

  /**
   * ResolveSourceLocation converts the relative source path into an absolute path.
   *
   * The path returned will be relative to the package installation location.
   *
   * @param string $package_name
   *   Name of the package containing the source file.
   * @param string $package_path
   *   Path to the root of the named package.
   * @param string $source
   *   Source location provided as a relative path.
   *
   * @return string
   *   Source location converted to an absolute path, or empty if removed.
   */
  public function resolveSourceLocation(string $package_name, string $package_path, string $source) {
    if (empty($source)) {
      return '';
    }

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
