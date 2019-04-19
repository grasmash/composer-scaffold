<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Composer\IO\IOInterface;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;

/**
 * Scaffold operation to copy from source to destination.
 */
class CopyOp extends ReplaceOp {

  /**
   * Process the replace operation. This could be a copy or a symlink.
   */
  public function placeScaffold(ScaffoldFileInfo $scaffold_file, IOInterface $io, array $options) {
    $interpolator = $scaffold_file->getInterpolator();
    $source_path = $this->getSource()->fullPath();
    $destination_path = $scaffold_file->getDestinationFullPath();

    $success = copy($source_path, $destination_path);
    if (!$success) {
      throw new \Exception($interpolator->interpolate("Could not copy source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>!", $this->interpolationData()));
    }

    $io->write($interpolator->interpolate("  - copy source file <info>[src-rel-path]</info> to <info>[dest-rel-path]</info>", $this->interpolationData()));
  }

}
