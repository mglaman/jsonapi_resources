<?php

namespace Drupal\jsonapi_resources\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jsonapi\Routing\Routes as JsonapiRoutes;
use Drupal\jsonapi_resources\JsonapiResourceManagerInterface;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\ResourceInterface;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\ResourceWithPermissionsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ResourceRoutes implements ContainerInjectionInterface {

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
      $jsonapi_resource = $this->jsonapiResourceManager->createInstance($plugin_id);
      assert($jsonapi_resource instanceof ResourceInterface);
      $requirements = [
        '_custom_access' => 'Drupal\jsonapi_resources\Controller\Handler::access',
      ];
      if ($jsonapi_resource instanceof ResourceWithPermissionsInterface) {
        $requirements['_permission'] = $jsonapi_resource->permission();
      }

      // @todo this feels too magical.
      $options = [];
      if (!empty($plugin_definition['route_parameters'])) {
        foreach ($plugin_definition['route_parameters'] as $parameter_name => $parameter_type) {
          $options[$parameter_name]['type'] = $parameter_type;
        }
      }

      $route = new Route($plugin_definition['uri_path'],
        [
          '_controller' => 'Drupal\jsonapi_resources\Controller\Handler::handle',
          '_jsonapi_resource' => $plugin_id,
        ],
        $requirements,
        $options,
        '',
        [],
        [$plugin_definition['method']]
      );
      $route_name_base = str_replace(':', '.', $plugin_id);
      $routes->add('jsonapi_resource.' . $route_name_base, $route);
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
