<?php

namespace Grasmash\ComposerScaffold\tests;

use Grasmash\ComposerScaffold\Handler;
use PHPUnit\Framework\TestCase;
use Composer\Util\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Tests Array Merge Recursive Distinct.
 */
class ArrayMergeRecursiveDistinctTest extends TestCase {

  /**
   * Tests ArrayManipulator::arrayMergeRecursiveExceptEmpty().
   *
   * @dataProvider providerTestArrayMergeRecursiveDistinct
   */
  public function testArrayMergeRecursiveDistinct(
    $array1,
    $array2,
    $expected_array
  ) {
    $this->assertEquals(Handler::arrayMergeRecursiveDistinct($array1,
      $array2), $expected_array);
  }

  /**
   * Provides values to testArrayMergeRecursiveDistinct().
   *
   * @return array
   *   An array of values to test.
   */
  public function providerTestArrayMergeRecursiveDistinct() {
    return [
      [
        [
          "drupal/core" => [
            "assets/.htaccess" => "[web-root]/.htaccess",
            "assets/robots-default.txt" => "[web-root]/robots.txt",
            "assets/index.php" => "[web-root]/index.php",
          ],
        ],
        [
          "drupal/core" => [
            "assets/.htaccess" => FALSE,
            "assets/robots-default.txt" => "[web-root]/robots.txt.bak",
          ],
        ],
        [
          "drupal/core" => [
            "assets/.htaccess" => FALSE,
            "assets/robots-default.txt" => "[web-root]/robots.txt.bak",
            "assets/index.php" => "[web-root]/index.php",
          ],
        ],
      ],
    ];
  }

}
