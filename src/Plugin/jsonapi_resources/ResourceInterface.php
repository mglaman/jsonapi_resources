<?php

namespace Drupal\jsonapi_resources\Plugin\jsonapi_resources;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Interface for JSON:API Resource plugins.
 *
 * This plugin implements CacheableDependencyInterface so that plugins may
 * define additional cache contexts and tags that should be appended to any
 * cacheable response beyond the cache metadata in the contained JSON:API
 * response data.
 */
interface ResourceInterface extends PluginInspectionInterface, CacheableDependencyInterface {

  /**
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\jsonapi\JsonApiResource\ResourceObjectData
   */
  public function process(RouteMatchInterface $route_match, Request $request): ResourceObjectData;

  /**
   * Checks access for the plugin.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account): AccessResult;

}
