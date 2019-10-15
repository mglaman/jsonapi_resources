<?php

namespace Drupal\jsonapi_resources\Plugin\jsonapi_resources;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

abstract class ResourceBase extends PluginBase implements ContainerFactoryPluginInterface, ResourceInterface {

  /**
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The JSON:API controller.
   *
   * @var \Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim
   */
  protected $inner;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ResourceTypeRepositoryInterface $resource_type_repository,
    EntityResourceShim $jsonapi_controller
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->resourceTypeRepository = $resource_type_repository;
    $this->inner = $jsonapi_controller;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jsonapi.resource_type.repository'),
      $container->get('jsonapi_resources.jsonapi_controller_shim')
    );
  }

  protected function availableMethods() {
    return array_filter(['GET', 'POST', 'PATCH', 'DELETE'], static function ($method) {
      return (method_exists(static::class, strtolower($method)));
    });
  }

  public function permissions() {
    $permissions = [];
    $definition = $this->getPluginDefinition();
    foreach ($this->availableMethods() as $method) {
      $lowered_method = strtolower($method);
      $permissions["jsonapi_resources $lowered_method $this->pluginId"] = [
        'title' => new TranslatableMarkup('Access @method on %label resource', ['@method' => $method, '%label' => $definition['label']]),
      ];
    }
    return $permissions;
  }

  public function routes() {
    $collection = new RouteCollection();
    $plugin_definition = $this->getPluginDefinition();

    $route_name_base = str_replace(':', '.', $this->pluginId);
    foreach ($this->availableMethods() as $method) {
      $route = $this->getBaseRoute($plugin_definition['uri_path'], $method);
      $collection->add("jsonapi_resources.$route_name_base.$method", $route);
    }

    return $collection;
  }

  protected function getBaseRoute($uri_path, $method) {
    return new Route($uri_path, [
      '_controller' => 'Drupal\jsonapi_resources\Controller\Handler::handle',
    ],
      $this->getBaseRouteRequirements($method),
      [],
      '',
      [],
      [$method]
    );
  }

  protected function getBaseRouteRequirements($method) {
    $requirements = [
      '_access' => 'TRUE',
    ];
    $permissions = $this->permissions();
    $lowered_method = strtolower($method);
    if (isset($permissions["jsonapi_resources $lowered_method $this->pluginId"])) {
      $requirements['_permission'] = "jsonapi_resources $lowered_method $this->pluginId";
    }

    return $requirements;
  }

}
