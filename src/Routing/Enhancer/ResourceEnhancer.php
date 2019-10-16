<?php

namespace Drupal\jsonapi_resources\Routing\Enhancer;

use Drupal\Core\Routing\EnhancerInterface;
use Symfony\Component\HttpFoundation\Request;

final class ResourceEnhancer implements EnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!isset($defaults['_jsonapi_resource'])) {
      return $defaults;
    }

    $defaults['_controller'] = $defaults['_jsonapi_resource'];
    unset($defaults['_jsonapi_resource']);
    return $defaults;
  }

}
