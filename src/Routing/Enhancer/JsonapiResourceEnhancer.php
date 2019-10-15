<?php

namespace Drupal\jsonapi_resources\Routing\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

final class JsonapiResourceEnhancer implements EnhancerInterface {

  /**
   * Returns whether the enhancer runs on the current route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   *
   * @return bool
   */
  protected function applies(Route $route) {
    return $route->hasDefault('_jsonapi_resource') && !$route->hasDefault('_controller');
  }


  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    if (!$this->applies($route)) {
      return $defaults;
    }

    $defaults['_controller'] = 'jsonapi_resource.controller:getContentResult';
    return $defaults;
  }
}
