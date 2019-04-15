<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\Package\PackageInterface;
use Composer\Script\Event;
use Composer\Composer;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;

/**
 * Scaffold operation to copy from source to destination.
 */
class ScaffoldCopyOp extends ScaffoldReplaceOp {

  /**
   * Process the replace operation. This could be a copy or a symlink.
   */
  public function placeScaffold(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    $interpolator = $scaffold_file->getInterpolator();
    $source_path = $this->getSourceFullPath();
    $destination_path = $scaffold_file->getDestinationFullPath();

    $success = copy($source_path, $destination_path);
    if (!$success) {
      throw new \Exception($interpolator->interpolate("Could not copy source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!", $this->interpolationData()));
    }

    $io->write($interpolator->interpolate("  - copy source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>", $this->interpolationData()));
  }

}
