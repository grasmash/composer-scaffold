<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold;

use Symfony\Component\Process\Process;

/**
 * Manage the .gitignore file.
 */
class ManageGitIgnore {

  protected $dir;

  /**
   * ManageGitIgnore constructor.
   *
   * @param string $dir
   *   The directory where the project is located.
   */
  public function __construct(string $dir) {
    $this->dir = $dir;
  }

  /**
   * Determine whether the specified scaffold file is already ignored.
   *
   * @param string $path
   *   Path to scaffold file to check.
   *
   * @return bool
   *   Whether the specified file is already ignored or not (TRUE if ignored).
   */
  public function checkIgnore(string $path) {
    $process = new Process('git check-ignore ' . $path, $this->dir);
    $process->run();
    $isIgnored = ($process->getExitCode() == 0);

    return $isIgnored;
  }

  /**
   * Determine whether the specified scaffold file is tracked in the repository.
   *
   * @param string $path
   *   Path to scaffold file to check.
   *
   * @return bool
   *   Whether the specified file is already tracked or not (TRUE if tracked).
   */
  public function checkTracked(string $path) {
    $process = new Process('git ls-files --error-unmatch ' . $path, $this->dir);
    $process->run();
    $isTracked = ($process->getExitCode() == 0);

    return $isTracked;
  }

  /**
   * Check to see if the project root dir is in a git repository.
   *
   * @return bool
   *   True if this is a repository.
   */
  public function isRepository() {
    $process = new Process('git rev-parse --show-toplevel', $this->dir);
    $process->run();
    $isRepository = ($process->getExitCode() == 0);

    return $isRepository;
  }

  /**
   * Check to see if the vendor directory is git ignored.
   *
   * @return bool
   *   True if 'vendor' is committed, or false if it is ignored.
   */
  public function vendorCommitted() {
    return $this->checkTracked('vendor');
  }

  /**
   * Determine whether we should manage gitignore files.
   *
   * @param array $options
   *   Configuration options from the composer.json extras section.
   *
   * @return bool
   *   Whether or not gitignore files should be managed.
   */
  public function managementOfGitIgnoreEnabled(array $options) {
    // If the composer.json stipulates whether gitignore is managed or not,
    // then follow its recommendation.
    if (isset($options['gitignore'])) {
      return $options['gitignore'];
    }
    // Do not manage .gitignore if there is no repository here.
    if (!$this->isRepository()) {
      return FALSE;
    }
    // If the composer.json did not specify whether or not gitignore files should
    // be managed, then manage them if the vendor directory is not committed.
    return !$this->vendorCommitted();
  }

  /**
   * Manage gitignore files.
   *
   * @param array $files
   *   A list of scaffold results, each of which holds a path and whether
   *   or not that file is managed.
   * @param array $options
   *   Configuration options from the composer.json extras section.
   */
  public function manageIgnored(array $files, array $options) {
    if (!$this->managementOfGitIgnoreEnabled($options)) {
      return;
    }
    // Accumulate entried to add to .gitignore, sorted into buckets based
    // on the location of the .gitignore file the entry should be added to.
    $addToGitIgnore = [];
    foreach ($files as $scaffoldResult) {
      $isIgnored = $this->checkIgnore($scaffoldResult->destination()->fullPath());
      $isTracked = $this->checkTracked($scaffoldResult->destination()->fullPath());
      if (!$isIgnored && !$isTracked && $scaffoldResult->isManaged()) {
        $path = $scaffoldResult->destination()->fullPath();
        $dir = dirname($path);
        $name = basename($path);
        $addToGitIgnore[$dir][] = $name;
      }
    }
    // Write out the .gitignore files one at a time.
    foreach ($addToGitIgnore as $dir => $entries) {
      $this->addToGitIgnore($dir, $entries);
    }
  }

  /**
   * Add a set of entries to the specified .gitignore file.
   *
   * @param string $dir
   *   Path to directory where gitignore should be written.
   * @param string[] $entries
   *   Entries to write to .gitignore file.
   */
  public function addToGitIgnore(string $dir, array $entries) {
    sort($entries);
    $gitIgnorePath = $dir . '/.gitignore';

    $contents = $this->gitIgnoreContents($gitIgnorePath);
    $contents .= implode("\n", $entries);
    file_put_contents($gitIgnorePath, $contents);
  }

  /**
   * Fetch the current contents of the specified .gitignore file.
   *
   * @param string $gitIgnorePath
   *   Path to .gitignore file.
   *
   * @return string
   *   Contents of .gitignore. Will always end with a "\n" unless empty.
   */
  public function gitIgnoreContents($gitIgnorePath) {
    if (!file_exists($gitIgnorePath)) {
      return '';
    }
    $contents = file_get_contents($gitIgnorePath);
    if (!empty($contents) && (substr($contents, -1) != "\n")) {
      $contents .= "\n";
    }
    return $contents;
  }

}
