<?php

namespace Drupal\jsonapi_resources\Plugin\jsonapi_resources;

use Drupal\Component\Plugin\PluginInspectionInterface;

interface ResourceWithPermissionsInterface extends ResourceInterface {

  /**
   * Provides a permissions suitable for .permissions.yml files.
   *
   * @return string
   *   The permission.
   */
  public function permission();

}
