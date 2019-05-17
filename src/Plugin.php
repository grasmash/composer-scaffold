<?php

namespace Grasmash\ComposerScaffold;

use Composer\Script\Event;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\CommandEvent;

/**
 * Composer plugin for handling drupal scaffold.
 */
class Plugin implements PluginInterface, EventSubscriberInterface, Capable {
  /**
   * The Composer Scaffold handler.
   *
   * @var \Grasmash\ComposerScaffold\Handler
   */
  protected $handler;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    // We use a separate PluginScripts object. This way we separate
    // functionality and also avoid some debug issues with the plugin being
    // copied on initialisation.
    // @see \Composer\Plugin\PluginManager::registerPackage()
    $this->handler = new Handler($composer, $io);
  }

  /**
   * {@inheritdoc}
   */
  public function getCapabilities() {
    return ['Composer\\Plugin\\Capability\\CommandProvider' => 'Grasmash\\ComposerScaffold\\CommandProvider'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_UPDATE_CMD => 'postCmd',
      PackageEvents::POST_PACKAGE_INSTALL => 'postPackage',
      PluginEvents::COMMAND => 'onCommand'
    ];
  }

  /**
   * Post command event callback.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public function postCmd(Event $event) {
    $this->handler->onPostCmdEvent($event);
  }

  /**
   * Post package event behaviour.
   *
   * @param \Composer\Installer\PackageEvent $event
   *   Composer package event sent on install/update/remove.
   */
  public function postPackage(PackageEvent $event) {
    $this->handler->onPostPackageEvent($event);
  }

  /**
   * Pre command event callback.
   *
   * @param \Composer\Plugin\CommandEvent $event
   *   The Composer command event.
   */
  public function onCommand(CommandEvent $event) {
    if ($event->getCommandName() == 'require') {
      $this->handler->beforeRequire($event);
    }
  }

}
