<?php

namespace Grasmash\ComposerScaffold\Tests;

use Symfony\Component\Process\Process;

/**
 * Convenience class for creating fixtures.
 */
trait ExecTrait {

  /**
   * Runs an arbitrary command.
   *
   * @param string $cmd
   *   The command to execute (escaped as required)
   * @param string $cwd
   *   The current working directory to run the command from.
   *
   * @return array
   *   Standard output and standard error from the command
   */
  protected function mustExec(string $cmd, string $cwd) {
    $process = new Process($cmd, $cwd);
    $process->setTimeout(300)->setIdleTimeout(300)->run();
    $exitCode = $process->getExitCode();
    if (0 != $exitCode) {
      throw new \Exception("Exit code: $exitCode\n\n" . $process->getErrorOutput());
    }
    return [$process->getOutput(), $process->getErrorOutput()];
  }

}
