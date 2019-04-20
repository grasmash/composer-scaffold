<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;
use Grasmash\ComposerScaffold\Interpolator;

/**
 * OperationCollection keeps track of the collection of files to be scaffolded.
 */
class OperationCollection {

  protected $listOfScaffoldFiles;
  protected $resolvedFileMappings;
  protected $io;

  /**
   * OperationCollection constructor.
   *
   * @param \Composer\IO\IOInterface $io
   *   A reference to the IO object, to allow us to write progress messages
   *   as we process scaffold operations.
   */
  public function __construct(IOInterface $io) {
    $this->io = $io;
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
   * @param \Grasmash\ComposerScaffold\ScaffoldFileInfo $scaffold_file
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
   * @param \Grasmash\ComposerScaffold\Interpolator $locationReplacements
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

        // If there was already a scaffolding operation happening at this
        // path, then pass it along to the new scaffold op, if it cares.
        if (isset($list_of_scaffold_files[$destination_rel_path]) && ($op instanceof OriginalOpAwareInterface)) {
          $op->setOriginalOp($list_of_scaffold_files[$destination_rel_path]->op());
        }

        $list_of_scaffold_files[$destination_rel_path] = $scaffold_file;
        $resolved_file_mappings[$package_name][$destination_rel_path] = $scaffold_file;
      }
    }
    $this->listOfScaffoldFiles = $list_of_scaffold_files;
    $this->resolvedFileMappings = $resolved_file_mappings;
  }

  /**
   * Scaffolds the files in our scaffold collection, package-by-package.
   *
   * @param array $options
   *   Configuration options from the top-level composer.json file.
   */
  public function processScaffoldFiles(array $options) {

    // We could simply scaffold all of the files from $list_of_scaffold_files,
    // which contain only the list of files to be processed. We iterate over
    // $resolved_file_mappings instead so that we can print out all of the
    // scaffold files grouped by the package that provided them, including
    // those not being scaffolded (because they were overridden or removed
    // by some later package).
    foreach ($this->fileMappings() as $package_name => $package_scaffold_files) {
      $this->io->write("Scaffolding files for <comment>$package_name</comment>:");
      foreach ($package_scaffold_files as $dest_rel_path => $scaffold_file) {
        $overriding_package = $this->findProvidingPackage($scaffold_file);
        if ($scaffold_file->overridden($overriding_package)) {
          $this->io->write($scaffold_file->interpolate("  - Skip <info>[dest-rel-path]</info>: overridden in <comment>$overriding_package</comment>"));
        }
        else {
          $scaffold_file->process($this->io, $options);
        }
      }
    }
  }

}
