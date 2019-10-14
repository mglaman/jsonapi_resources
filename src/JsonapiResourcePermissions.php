<?php

namespace Drupal\jsonapi_resources;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\JsonapiResourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JsonapiResourcePermissions implements ContainerInjectionInterface {

  /**
   * @var \Drupal\jsonapi_resources\JsonapiResourceManagerInterface
   */
  protected $jsonapiResourceManager;

  public function __construct(JsonapiResourceManagerInterface $jsonapi_resource_manager) {
    $this->jsonapiResourceManager = $jsonapi_resource_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.jsonapi_resource')
    );
  }

  public function permissions() {
    $plugins = $this->jsonapiResourceManager->getDefinitions();
    $permissions = [];
    foreach ($plugins as $plugin_id => $plugin_definition) {
      $plugin = $this->jsonapiResourceManager->createInstance($plugin_id);
      assert($plugin instanceof JsonapiResourceInterface);
      $permissions[] = $plugin->permissions();
    }
    return array_merge([], ...$permissions);
  }
}
