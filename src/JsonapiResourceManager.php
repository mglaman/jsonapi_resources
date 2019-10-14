<?php

namespace Drupal\jsonapi_resources;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class JsonapiResourceManager extends DefaultPluginManager implements JsonapiResourceManagerInterface {

  /**
   * Constructs a new \Drupal\rest\Plugin\Type\ResourcePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/jsonapi_resources', $namespaces, $module_handler, 'Drupal\jsonapi_resources\Plugin\jsonapi_resources\JsonapiResourceInterface', 'Drupal\jsonapi_resources\Annotation\JsonapiResource');
    $this->setCacheBackend($cache_backend, 'jsonapi_resources');
    $this->alterInfo('jsonapi_resource');
  }

  public function hasDefinition($plugin_id) {
    return (bool) $this->getDefinition($plugin_id);
  }
}
