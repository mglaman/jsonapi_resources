<?php

namespace Drupal\jsonapi_resources_test\Plugin\jsonapi_resources;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\JsonapiResourceBase;
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
 * )
 */
class AuthorContent extends JsonapiResourceBase {

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

  public function get(Request $request, RouteMatchInterface $route_match) {
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
    $response = $this->inner->buildWrappedResponse($data, $request, $this->inner->getIncludes($request, $data));
    // @note: this is one reason why the plugin invokes buildWrappedResponse and
    // not within the controller. The plugin may need to add additional cache
    // metadata to the response.
    $response->addCacheableDependency($user);
    return $response;
  }

  protected function getBaseRoute($uri_path, $method) {
    $route = parent::getBaseRoute($uri_path, $method);
    $parameters = $route->getOption('parameters') ?: [];
    $parameters['user']['type'] = 'entity:user';
    $route->setOption('parameters', $parameters);

    return $route;
  }

}
