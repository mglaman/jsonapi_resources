<?php

namespace Drupal\jsonapi_resources\JsonapiResource;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

interface ResourceInterface {
  public function process(RouteMatchInterface $route_match, Request $request);
}
