<?php declare(strict_types=1);

namespace Drupal\jsonapi_resources\Resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\ResourceResponseFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntityResourceBase extends ResourceBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityResourceBase object.
   *
   * @param \Drupal\jsonapi_resources\ResourceResponseFactory $resource_response_factory
   *   The resource response factory.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Tne entity type manager.
   */
  public function __construct(ResourceResponseFactory $resource_response_factory, ResourceTypeRepositoryInterface $resource_type_repository, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($resource_response_factory, $resource_type_repository);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi_resource.resource_response_factory'),
      $container->get('jsonapi.resource_type.repository'),
      $container->get('entity_type.manager')
    );
  }

}
