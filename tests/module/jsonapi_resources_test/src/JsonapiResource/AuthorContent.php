<?php

namespace Drupal\jsonapi_resources_test\Plugin\jsonapi_resources;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\CacheableJsonapiResponse;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\ResourceBase;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\ResourceWithPermissionsInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @JsonapiResource(
 *   id = "author_content",
 *   label = "Author content",
 *   definition = "Returns content created by a user",
 *   uri_path = "/{user}/content",
 *   method="GET",
 *   route_parameters={
 *     "user" = "entity:user",
 *   }
 * )
 */
class AuthorContent extends ResourceBase implements ResourceWithPermissionsInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jsonapi.resource_type.repository'),
      $container->get('jsonapi_resources.jsonapi_controller_shim'),
      $container->get('entity_type.manager')
    );
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ResourceTypeRepositoryInterface $resource_type_repository, EntityResourceShim $jsonapi_controller, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $resource_type_repository, $jsonapi_controller);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function permission() {
    return 'jsonapi_resources get author_content';
  }

  public function process(RouteMatchInterface $route_match, Request $request): CacheableJsonapiResponse {
    // Force the author to be included.
    $include = $request->query->get('include');
    $request->query->set('include', $include . (empty($include) ? '' : ',') . 'uid');

    $user = $route_match->getParameter('user');
    assert($user instanceof UserInterface);
    $node_storage = $this->entityTypeManager->getStorage('node');
    $featured_query = $node_storage->getQuery();
    $featured_query
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('uid', $user->id());

    $nodes = $node_storage->loadMultiple($featured_query->execute());
    $data = new ResourceObjectData(array_map(function (NodeInterface $node) {
      $resource_type = $this->resourceTypeRepository->get($node->getEntityTypeId(), $node->bundle());
      return ResourceObject::createFromEntity($resource_type, $node);
    }, $nodes));

    // basically buildWrapped response, performs getIncludes
    $response = CacheableJsonapiResponse::createFromData($data);
    $response->addCacheableDependency($user);
    return $response;
  }

}