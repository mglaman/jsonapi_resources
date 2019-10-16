<?php declare(strict_types=1);

namespace Drupal\jsonapi_resources\Resource;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\ResourceResponseFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ResourceBase implements ContainerInjectionInterface {

  /**
   * @var \Drupal\jsonapi_resources\ResourceResponseFactory
   */
  protected $resourceResponseFactory;

  protected $resourceTypeRepository;

  public function __construct(ResourceResponseFactory $resource_response_factory, ResourceTypeRepositoryInterface $resource_type_repository) {
    $this->resourceResponseFactory = $resource_response_factory;
    $this->resourceTypeRepository = $resource_type_repository;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi_resource.resource_response_factory'),
      $container->get('jsonapi.resource_type.repository')
    );
  }

}
