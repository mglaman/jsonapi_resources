<?php

namespace Drupal\jsonapi_resources;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;

final class CacheableJsonapiResponse extends ResourceResponse {
  public static function createFromData(ResourceObjectData $data, Request $request, $response_code = 200, array $headers = [], LinkCollection $links = NULL, array $meta = []) {
    $links = ($links ?: new LinkCollection([]));
    if (!$links->hasLinkWithKey('self')) {
      $self_link = new Link(new CacheableMetadata(), self::getRequestLink($request), ['self']);
      $links = $links->withLink('self', $self_link);
    }

    $includes = $request->query->has('include') && ($include_parameter = $request->query->get('include')) && !empty($include_parameter)
      ? \Drupal::getContainer()->get('jsonapi.include_resolver')->resolve($data, $include_parameter)
      : new NullIncludedData();

    $response = new ResourceResponse(new JsonApiDocumentTopLevel($data, $includes, $links, $meta), $response_code, $headers);
    $cacheability = (new CacheableMetadata())->addCacheContexts([
      // Make sure that different sparse fieldsets are cached differently.
      'url.query_args:fields',
      // Make sure that different sets of includes are cached differently.
      'url.query_args:include',
    ]);
    $response->addCacheableDependency($cacheability);
    return $response;
  }

  /**
   * Get the full URL for a given request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param array|null $query
   *   The query parameters to use. Leave it empty to get the query from the
   *   request object.
   *
   * @return \Drupal\Core\Url
   *   The full URL.
   */
  protected static function getRequestLink(Request $request, $query = NULL) {
    if ($query === NULL) {
      return Url::fromUri($request->getUri());
    }

    $uri_without_query_string = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
    return Url::fromUri($uri_without_query_string)->setOption('query', $query);
  }

}
