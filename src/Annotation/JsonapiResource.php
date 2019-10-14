<?php

namespace Drupal\jsonapi_resources\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
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

}
