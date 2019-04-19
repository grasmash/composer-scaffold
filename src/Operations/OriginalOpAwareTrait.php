<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

/**
 * Use OriginalOpAwareTrait to be informed of any op at the same destination path.
 */
trait OriginalOpAwareTrait {

  /**
   * The original operation at the same destination path.
   *
   * @var OperationInterface
   *   The original operation at the same destination path.
   */
  protected $originalOp;

  /**
   * Set a reference to the original scaffold operation at the same destination path.
   *
   * @param OperationInterface $originalOp
   *   The scaffold operation for the source file being appended / prepended.
   *
   * @return $this
   */
  public function setOriginalOp(OperationInterface $originalOp) {
    $this->originalOp = $originalOp;
    return $this;
  }

  /**
   * Return 'true' if an original operation was provided.
   *
   * @return bool
   *   Whether or not an original operation was provided.
   */
  public function hasOriginalOp() {
    return isset($this->originalOp);
  }

  /**
   * Return the original operation that this op is overriding.
   *
   * @return OperationInterface
   *   The original operation.
   */
  public function originalOp() {
    return $this->originalOp;
  }

}
