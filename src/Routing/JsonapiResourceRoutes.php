<?php

namespace Drupal\jsonapi_resources\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Routing\Routes as JsonapiRoutes;
use Drupal\jsonapi_resources\JsonapiResourceManagerInterface;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\JsonapiResourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

class JsonapiResourceRoutes implements ContainerInjectionInterface {

  /**
   * List of providers.
   *
   * @var string[]
   */
  protected $providerIds;

  /**
   * The JSON:API base path.
   *
   * @var string
   */
  protected $jsonApiBasePath;

  /**
   * @var \Drupal\jsonapi_resources\JsonapiResourceManagerInterface
   */
  protected $jsonapiResourceManager;

  /**
   * Instantiates a Routes object.
   *
   * @param string[] $authentication_providers
   *   The authentication providers, keyed by ID.
   * @param string $jsonapi_base_path
   *   The JSON:API base path.
   */
  public function __construct(JsonapiResourceManagerInterface $jsonapi_resource_manager, array $authentication_providers, $jsonapi_base_path) {
    $this->jsonapiResourceManager = $jsonapi_resource_manager;
    $this->providerIds = array_keys($authentication_providers);
    assert(is_string($jsonapi_base_path));
    assert(
      strpos($jsonapi_base_path, '/') === 0,
      sprintf('The provided base path should contain a leading slash "/". Given: "%s".', $jsonapi_base_path)
    );
    assert(
      substr($jsonapi_base_path, -1) !== '/',
      sprintf('The provided base path should not contain a trailing slash "/". Given: "%s".', $jsonapi_base_path)
    );
    $this->jsonApiBasePath = $jsonapi_base_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.jsonapi_resource'),
      $container->getParameter('authentication_providers'),
      $container->getParameter('jsonapi.base_path')
    );
  }


  public function routes() {
    $routes = new RouteCollection();

    $plugins = $this->jsonapiResourceManager->getDefinitions();
    foreach ($plugins as $plugin_id => $plugin_definition) {
      $plugin_routes = new RouteCollection();
      $jsonapi_resource = $this->jsonapiResourceManager->createInstance($plugin_id);
      assert($jsonapi_resource instanceof JsonapiResourceInterface);
      $plugin_routes->addCollection($jsonapi_resource->routes());
      $plugin_routes->addDefaults(['_jsonapi_resource' => $plugin_id]);
      $routes->addCollection($plugin_routes);
    }

    // Ensure JSON API prefix.
    $routes->addPrefix($this->jsonApiBasePath);

    // Require the JSON:API media type header on every route, except on file
    // upload routes, where we require `application/octet-stream`.
    $routes->addRequirements(['_content_type_format' => 'api_json']);

    // Enable all available authentication providers.
    $routes->addOptions(['_auth' => $this->providerIds]);

    // Flag every route as belonging to the JSON:API module.
    $routes->addDefaults([JsonapiRoutes::JSON_API_ROUTE_FLAG_KEY => TRUE]);

    // All routes serve only the JSON:API media type.
    $routes->addRequirements(['_format' => 'api_json']);

    return $routes;
  }
}
