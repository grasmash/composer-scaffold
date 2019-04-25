<?php

namespace Grasmash\ComposerScaffold\Tests\Integration;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Grasmash\ComposerScaffold\Handler;
use PHPUnit\Framework\TestCase;
use Grasmash\ComposerScaffold\ScaffoldFileInfo;
use Grasmash\ComposerScaffold\Tests\Fixtures;
use Grasmash\ComposerScaffold\Operations\SkipOp;
use Grasmash\ComposerScaffold\Operations\OperationCollection;

/**
 * @coversDefaultClass \Grasmash\ComposerScaffold\Operations\OperationCollection
 */
class OperationCollectionTest extends TestCase {

  /**
   * @covers ::coalateScaffoldFiles
   */
  public function testCoalateScaffoldFiles() {
    $fixtures = new Fixtures();

    $locationReplacements = $fixtures->getLocationReplacements();

    $file_mappings = [
      'fixtures/drupal-assets-fixture' => [
        '[web-root]/index.php' => $fixtures->replaceOp('drupal-assets-fixture', 'index.php'),
        '[web-root]/.htaccess' => $fixtures->replaceOp('drupal-assets-fixture', '.htaccess'),
        '[web-root]/robots.txt' => $fixtures->replaceOp('drupal-assets-fixture', 'robots.txt'),
        '[web-root]/sites/default/default.services.yml' => $fixtures->replaceOp('drupal-assets-fixture', 'default.services.yml'),
      ],
      'fixtures/drupal-profile' => [
        '[web-root]/sites/default/default.services.yml' => $fixtures->replaceOp('drupal-profile', 'profile.default.services.yml'),
      ],
      'fixtures/drupal-drupal' => [
        '[web-root]/.htaccess' => new SkipOp(),
        '[web-root]/robots.txt' => $fixtures->appendOp('drupal-drupal-test-append', 'append-to-robots.txt'),
      ],
    ];

    $sut = new OperationCollection($fixtures->io());

    // Test the system under test.
    $sut->coalateScaffoldFiles($file_mappings, $locationReplacements);
    $resolved_file_mappings = $sut->fileMappings();
    $scaffold_list = $sut->scaffoldList();

    // Confirm that the keys of the output are the same as the keys of the input.
    $this->assertEquals(array_keys($file_mappings), array_keys($resolved_file_mappings));

    // Also assert that we have the right ScaffoldFileInfo objects in the destination.
    $this->assertResolvedToSameOp('fixtures/drupal-assets-fixture', '[web-root]/index.php', $file_mappings, $scaffold_list, $resolved_file_mappings);
    $this->assertResolvedToSameOp('fixtures/drupal-profile', '[web-root]/sites/default/default.services.yml', $file_mappings, $scaffold_list, $resolved_file_mappings);
    $this->assertResolvedToSameOp('fixtures/drupal-drupal', '[web-root]/robots.txt', $file_mappings, $scaffold_list, $resolved_file_mappings);

    // Assert that the files below have been overridden.
    $this->assertOverridden('fixtures/drupal-assets-fixture', '[web-root]/.htaccess', $scaffold_list, $resolved_file_mappings);
    $this->assertOverridden('fixtures/drupal-assets-fixture', '[web-root]/robots.txt', $scaffold_list, $resolved_file_mappings);
  }

  /**
   * Check to see if a given file was not overridden.
   *
   * The package name in the scaffold list for the provided destination should
   * match the package name from the specified project.
   */
  protected function assertResolvedToSameOp($project, $dest, $file_mappings, $scaffold_list, $resolved_file_mappings) {
    $resolved_file_info = $resolved_file_mappings[$project][$dest];
    $this->assertEquals(get_class($resolved_file_info), ScaffoldFileInfo::class);
    $resolved_scaffold_op = $resolved_file_info->op();
    $this->assertEquals(get_class($file_mappings[$project][$dest]), get_class($resolved_scaffold_op));
    $this->assertEquals($file_mappings[$project][$dest], $resolved_scaffold_op);

    $this->assertEquals($project, $scaffold_list[$dest]->packageName());
  }

  /**
   * Check if a given file was overridden.
   *
   * Assert that the file in the scaffold list at the specified destination
   * comes from a different package than the one in the file info.
   */
  protected function assertOverridden($project, $dest, $scaffold_list, $resolved_file_mappings) {
    $resolved_file_info = $resolved_file_mappings[$project][$dest];
    $this->assertEquals(get_class($resolved_file_info), ScaffoldFileInfo::class);
    $this->assertNotEquals($project, $scaffold_list[$dest]->packageName());
  }

}
