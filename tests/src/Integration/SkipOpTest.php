<?php

namespace Grasmash\ComposerScaffold\Tests\Integration;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Grasmash\ComposerScaffold\Handler;
use Grasmash\ComposerScaffold\Operations\SkipOp;
use Grasmash\ComposerScaffold\ScaffoldOptions;
use Grasmash\ComposerScaffold\Tests\Fixtures;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Grasmash\ComposerScaffold\Operations\SkipOp
 */
class SkipOpTest extends TestCase {

  /**
   * @covers ::process
   */
  public function testProcess() {
    $fixtures = new Fixtures();

    $destination = $fixtures->destinationPath('[web-root]/robots.txt');
    $source = $fixtures->sourcePath('drupal-assets-fixture', 'robots.txt');

    $options = ScaffoldOptions::defaultOptions();

    $sut = new SkipOp();

    // Assert that there is no target file before we run our test.
    $this->assertFileNotExists($destination->fullPath());

    // Test the system under test.
    $sut->process($destination, $fixtures->io(), $options);

    // Assert that the target file was not created.
    $this->assertFileNotExists($destination->fullPath());

    // Confirm that expected output was written to our io fixture.
    $output = $fixtures->getOutput();
    $this->assertContains('Skip [web-root]/robots.txt: disabled', $output);
  }

}
