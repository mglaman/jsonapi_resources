<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi_resources\Resource\EntityResourceBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

class FeaturedNodes extends EntityResourceBase {

  public function process(Request $request): ResourceResponse {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $featured_query = $node_storage->getQuery();
    $featured_query
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('promote', NodeInterface::PROMOTED);

    $nodes = $node_storage->loadMultiple($featured_query->execute());
    $data = new ResourceObjectData(array_map(function (NodeInterface $node) {
      $resource_type = $this->resourceTypeRepository->get($node->getEntityTypeId(), $node->bundle());
      return ResourceObject::createFromEntity($resource_type, $node);
    }, $nodes), 4);
    return $this->resourceResponseFactory->create($data, $request);
  }

}
