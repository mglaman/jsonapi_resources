<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityResourceBase;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;

class AuthorContent extends EntityResourceBase {

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(Request $request, UserInterface $user): ResourceResponse {
    // Force the author to be included.
    $include = $request->query->get('include');
    $request->query->set('include', $include . (empty($include) ? '' : ',') . 'uid');

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

    $response = $this->resourceResponseFactory->create($data, $request);
    $response->addCacheableDependency($user);
    return $response;
  }

}
