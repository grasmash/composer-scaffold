<?php

declare(strict_types = 1);

namespace Grasmash\ComposerScaffold\Operations;

/**
 * Implement OriginalOpAwareInterface to be informed of any op at the same destination path.
 */
interface OriginalOpAwareInterface {

  /**
   * Set a reference to the original scaffold operation at the same destination path.
   *
   * @param OperationInterface $originalOp
   *   The scaffold operation for the source file being appended / prepended.
   *
   * @return $this
   */
  public function setOriginalOp(OperationInterface $originalOp);

  /**
   * Return 'true' if an original operation was provided.
   *
   * @return bool
   *   Whether or not an original operation was provided.
   */
  public function hasOriginalOp() : bool;

  /**
   * Return the original operation that this op is overriding.
   *
   * @return OperationInterface
   *   The original operation.
   */
  public function originalOp() : OperationInterface;

}
