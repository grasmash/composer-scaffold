<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

use Grasmash\ComposerScaffold\ScaffoldFilePath;

/**
 * Record the result of a scaffold operation.
 */
class ScaffoldResult {

  protected $destination;
  protected $managed;

  /**
   * ScaffoldResult constructor.
   *
   * @param \Grasmash\ComposerScaffold\ScaffoldFilePath $destination
   *   The path to the scaffold file that was processed.
   */
  public function __construct(ScaffoldFilePath $destination) {
    $this->destination = $destination;
    $this->managed = FALSE;
  }

  /**
   * Determine whether this scaffold file is managed.
   *
   * @return bool
   *   Whether the scaffold file was managed by this plugin (scaffolded) or not (skipped).
   */
  public function isManaged() : bool {
    return $this->managed;
  }

  /**
   * Recored whether this result was managed or unmanaged.
   *
   * @param bool $isManaged
   *   Whether this result is managed.
   *
   * @return $this
   */
  public function setManaged(bool $isManaged = TRUE) {
    $this->managed = $isManaged;
    return $this;
  }

  /**
   * The destination scaffold file that this result refers to.
   *
   * @return \Grasmash\ComposerScaffold\ScaffoldFilePath
   *   The destination path for the scaffold result.
   */
  public function destination() : ScaffoldFilePath {
    return $this->destination;
  }

}
