<?php

namespace Drupal\jsonapi_resources\Controller;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jsonapi\Exception\UnprocessableHttpEntityException;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\JsonapiResourceManagerInterface;
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
    if(!$this->jsonapiResourceManager->hasDefinition($_jsonapi_resource)) {
      throw new AccessDeniedHttpException("$_jsonapi_resource does not exist");
    }

    // \Drupal\Core\Routing\MethodFilter ensures we have a valid HTTP method for
    // the current route.
    $method = strtolower($request->getMethod());
    $plugin = $this->jsonapiResourceManager->createInstance($_jsonapi_resource);
    if (!method_exists($plugin, $method)) {
      throw new AccessDeniedHttpException(sprintf('%s does not handle %s requests.', $_jsonapi_resource, strtoupper($method)));
    }

    $result = $plugin->$method($request, $route_match);
    if ($result !== NULL && !$result instanceof ResourceResponse) {
      throw new UnprocessableEntityHttpException(sprintf('Response expected %s but got %s', ResourceResponse::class, get_class($result)));
    }
    return $result;
  }

}
