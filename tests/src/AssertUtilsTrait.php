<?php

namespace Grasmash\ComposerScaffold\Tests;

/**
 * Convenience class for creating fixtures.
 */
trait AssertUtilsTrait {

  /**
   * Asserts that a given file exists and is/is not a symlink.
   */
  protected function assertScaffoldedFile($path, $is_link, $contents_contains) {
    $this->assertFileExists($path);
    $contents = file_get_contents($path);
    $this->assertRegExp($contents_contains, basename($path) . ': ' . $contents);
    $this->assertSame($is_link, is_link($path));
  }

}
