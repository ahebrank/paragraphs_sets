<?php

namespace Drupal\paragraphs_sets;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\Container;

/**
 * Provides a plugin manager for paragraphs_sets.
 *
 * @see plugin_api
 */
class PluginManager extends DefaultPluginManager {

  /**
   * Constructs a OutputManager object.
   *
   * @param string $type
   *   Plugin type.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(string $type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $camel_type = Container::camelize($type);

    parent::__construct(
      "Plugin/paragraphs_sets/$type",
      $namespaces,
      $module_handler,
      "Drupal\\paragraphs_sets\\Plugin\\paragraphs_sets\\$type\\${camel_type}PluginInterface",
      "Drupal\paragraphs_sets\Annotation\ParagraphsSets" . $camel_type
    );

    $this->alterInfo('paragraphs_sets_info_' . $type);
    $this->setCacheBackend($cache_backend, 'paragraph_sets:' . $type);
  }

}
