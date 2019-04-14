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

  protected $listOfScaffoldFiles;
  protected $resolvedFileMappings;

  /**
   * ScaffoldCollection constructor.
   */
  public function __construct() {
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
      foreach ($package_file_mappings as $destination_rel_path => $op) {
        $dest_full_path = $locationReplacements->interpolate($destination_rel_path);

        $scaffold_file = (new ScaffoldFileInfo())
          ->setPackageName($package_name)
          ->setDestinationRelativePath($destination_rel_path)
          ->setDestinationFullPath($dest_full_path)
          ->setOp($op);

        $list_of_scaffold_files[$destination_rel_path] = $scaffold_file;
        $resolved_file_mappings[$package_name][$destination_rel_path] = $scaffold_file;
      }
    }
    $this->listOfScaffoldFiles = $list_of_scaffold_files;
    $this->resolvedFileMappings = $resolved_file_mappings;
  }

}
