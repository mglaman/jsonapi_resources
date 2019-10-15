<?php

namespace Drupal\jsonapi_resources\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 *
 * add feature flags for support of JSON API params (filter, sort, page)
 */
class JsonapiResource extends Plugin {

  /**
   * The JSON:API resource plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the JSON:API resource plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The URI path for this JSON:API resource plugin.
   *
   * @var string
   */
  public $uri_path;

  /**
   * The HTTP method for the JSON:API resource.
   *
   * @var string
   *
   * @todo validate one of ['GET', 'POST', 'PATCH', 'DELETE']
   */
  public $method;

  /**
   * Route parameter definitions.
   *
   * @var array
   *
   * @todo this feels too fragile.
   */
  public $route_parameters = [];

}
