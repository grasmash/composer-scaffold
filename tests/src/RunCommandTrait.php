<?php

namespace Grasmash\ComposerScaffold\Tests;

use Symfony\Component\Process\Process;

/**
 * Convenience class for creating fixtures.
 */
trait RunCommandTrait {

  /**
   * Runs a `composer` command.
   *
   * @param string $cmd
   *   The Composer command to execute (escaped as required)
   * @param string $cwd
   *   The current working directory to run the command from.
   * @param int $expectedExitCode
   *   The expected exit code; will throw if a different exit code is returned.
   *
   * @return array
   *   Standard output and standard error from the command
   */
  protected function runComposer(string $cmd, string $cwd, int $expectedExitCode = 0) {
    return $this->runCommand("composer $cmd", $cwd, $expectedExitCode);
  }

  /**
   * Runs an arbitrary command.
   *
   * @param string $cmd
   *   The command to execute (escaped as required)
   * @param string $cwd
   *   The current working directory to run the command from.
   * @param int $expectedExitCode
   *   The expected exit code; will throw if a different exit code is returned.
   *
   * @return array
   *   Standard output and standard error from the command
   */
  protected function runCommand(string $cmd, string $cwd, int $expectedExitCode = 0) {
    $process = new Process($cmd, $cwd);
    $process->setTimeout(300)->setIdleTimeout(300)->run();
    $this->assertSame($expectedExitCode, $process->getExitCode(), $process->getErrorOutput());
    return [$process->getOutput(), $process->getErrorOutput()];
  }

}
