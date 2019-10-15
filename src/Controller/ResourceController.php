<?php

namespace Drupal\jsonapi_resources\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
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
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class ResourceController {

  /**
   * The argument resolver.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface
   */
  protected $argumentResolver;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  public function __construct(ArgumentResolverInterface $argument_resolver, ClassResolverInterface $class_resolver) {
    $this->argumentResolver = $argument_resolver;
    $this->classResolver = $class_resolver;
  }

  public function getContentResult(RouteMatchInterface $route_match, Request $request, $_jsonapi_resource) {
    $controller = $this->classResolver->getInstanceFromDefinition($_jsonapi_resource);
    assert($controller instanceof \Drupal\jsonapi_resources\JsonapiResource\ResourceInterface);
    return $controller->process($route_match, $request);
  }

}
