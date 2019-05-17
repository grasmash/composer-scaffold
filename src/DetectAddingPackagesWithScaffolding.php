<?php

namespace Grasmash\ComposerScaffold;

use Composer\Installer\PackageEvent;

/**
 * Manage new packages that are added via 'composer require'.
 *
 * This package manages examining all required packages, and informing the
 * allowed package manager whenever a newly-required package is found to
 * contain scaffolding instructions.
 */
class DetectAddingPackagesWithScaffolding {
  protected $manageAllowedPackages;

  /**
   * DetectAddingPackagesWithScaffolding constructor.
   *
   * @param AllowedPackages $manageAllowedPackages
   *   The manager that handles allowed packages. We will inform it when new packages are added.
   */
  public function __construct(AllowedPackages $manageAllowedPackages) {
    $this->manageAllowedPackages = $manageAllowedPackages;
  }

  /**
   * Handle package events during a 'composer require' operation.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Composer package event sent on install/update/remove.
   */
  public function event(PackageEvent $event) {
    $operation = $event->getOperation();
    $jobType = $operation->getJobType();
    $reason = $operation->getReason();
    // Get the package.
    $package = $operation->getJobType() == 'update' ? $operation->getTargetPackage() : $operation->getPackage();
    if (ScaffoldOptions::hasOptions($package->getExtra())) {
      $this->manageAllowedPackages->addedPackageWithScaffolding($package);
    }
  }

}
