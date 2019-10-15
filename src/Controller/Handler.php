<?php

namespace Drupal\jsonapi_resources\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Exception\UnprocessableHttpEntityException;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\JsonapiResourceManagerInterface;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\ResourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class Handler implements ContainerInjectionInterface {

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

  /**
   * The JSON:API serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface
   */
  protected $serializer;

  /**
   * @var \Drupal\jsonapi_resources\JsonapiResourceManagerInterface
   */
  protected $jsonapiResourceManager;


  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, SerializerInterface $serializer, JsonapiResourceManagerInterface $jsonapi_resource_manager, EntityResourceShim $jsonapi_controller) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->serializer = $serializer;
    $this->jsonapiResourceManager = $jsonapi_resource_manager;
    $this->inner = $jsonapi_controller;
  }

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('jsonapi.serializer'),
      $container->get('plugin.manager.jsonapi_resource'),
      $container->get('jsonapi_resources.jsonapi_controller_shim')
    );
  }

  public function handle(RouteMatchInterface $route_match, Request $request, $_jsonapi_resource) {
    $plugin = $this->jsonapiResourceManager->createInstance($_jsonapi_resource);
    assert($plugin instanceof ResourceInterface);
    $primary_data = $plugin->process($route_match, $request);
    $response = $this->inner->buildWrappedResponse($primary_data, $request, $this->inner->getIncludes($request, $primary_data));
    // If this is a cacheable response, ensure the cache contexts and cache tags
    // defined on the plugin are added to the request.
    if ($response instanceof CacheableResponseInterface) {
      $response->addCacheableDependency($plugin);
      // If any parameter is a cacheable dependency, ensure it is added to the
      // response cache metadata.
      foreach ($route_match->getParameters()->all() as $item) {
        if ($item instanceof CacheableDependencyInterface) {
          $response->addCacheableDependency($item);
        }
      }
    }
    return $response;
  }

  /**
   * Checks access for the JSON:API Resource.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $jsonapi_resource = $route_match->getParameter('_jsonapi_resource');
    if (!$this->jsonapiResourceManager->hasDefinition($jsonapi_resource)) {
      return AccessResult::forbidden("$jsonapi_resource plugin does not exist.");
    }
    $plugin = $this->jsonapiResourceManager->createInstance($jsonapi_resource);
    assert($plugin instanceof ResourceInterface);
    return $plugin->access($route_match, $account);
  }

}
