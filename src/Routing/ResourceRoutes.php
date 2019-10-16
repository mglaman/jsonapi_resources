<?php

namespace Drupal\jsonapi_resources\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\jsonapi\Routing\Routes as JsonapiRoutes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ResourceRoutes implements EventSubscriberInterface {

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
   * Instantiates a Routes object.
   *
   * @param string[] $authentication_providers
   *   The authentication providers, keyed by ID.
   * @param string $jsonapi_base_path
   *   The JSON:API base path.
   */
  public function __construct(array $authentication_providers, $jsonapi_base_path) {
    $this->providerIds = array_keys($authentication_providers);
    $this->jsonApiBasePath = $jsonapi_base_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before route_http_method_subscriber, so that we can ensure JSON:API
    // Resource routes default to only GET methods if not set.
    $events[RoutingEvents::ALTER][] = ['decorateJsonapiResourceRoutes', 6000];
    return $events;
  }

  public function decorateJsonapiResourceRoutes(RouteBuildEvent $event) {
    foreach ($event->getRouteCollection() as $route) {
      if ($route->getDefault('_jsonapi_resource') === NULL) {
        continue;
      }
      $route->setPath('/' . $this->jsonApiBasePath . $route->getPath());
      $route->addRequirements([
        // Require the JSON:API media type header on every route.
        '_content_type_format' => 'api_json',
        // All routes serve only the JSON:API media type.
        '_format' => 'api_json',
      ]);

      // Enable all available authentication providers.
      $route->addOptions(['_auth' => $this->providerIds]);
      // Flag every route as belonging to the JSON:API module.
      $route->addDefaults([JsonapiRoutes::JSON_API_ROUTE_FLAG_KEY => TRUE]);

      $methods = $route->getMethods();
      if (empty($methods)) {
        $route->setMethods(['GET']);
      }
    }
  }

}
