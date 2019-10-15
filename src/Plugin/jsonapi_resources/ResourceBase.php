<?php

namespace Drupal\jsonapi_resources\Plugin\jsonapi_resources;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ResourceTypeRepositoryInterface $resource_type_repository, EntityResourceShim $jsonapi_controller) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->resourceTypeRepository = $resource_type_repository;
    $this->inner = $jsonapi_controller;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jsonapi.resource_type.repository'),
      $container->get('jsonapi_resources.jsonapi_controller_shim')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account): AccessResult {
    if ($this instanceof ResourceWithPermissionsInterface) {
      return AccessResult::allowedIfHasPermission($account, $this->permission());
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return Cache::PERMANENT;
  }

}
