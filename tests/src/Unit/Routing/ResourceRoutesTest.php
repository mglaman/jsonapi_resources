<?php declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Unit;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\jsonapi_resources\Routing\ResourceRoutes;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\jsonapi_resources\Routing\ResourceRoutes
 */
final class ResourceRoutesTest extends UnitTestCase {

  public function testDecoratedRouteCollection() {
    $route_collection = new RouteCollection();
    $route_collection->add('generic_route', new Route('/generic'));
    $route_collection->add('jsonapi_resource_route', new Route('/resource', ['_jsonapi_resource' => '\\Drupal\\mymodule\\Resource::test']));
    $route_collection->add('jsonapi_resource_multi_method_route', new Route('/resource', ['_jsonapi_resource' => '\\Drupal\\mymodule\\Resource::test'], [], [], '', [], ['POST', 'PATCH']));

    $route_rebuild_event = new RouteBuildEvent($route_collection);

    $resource_routes = new ResourceRoutes(['basic_auth' => 'basic_auth'], '/jsonapi');
    $resource_routes->decorateJsonapiResourceRoutes($route_rebuild_event);

    $generic_route = $route_collection->get('generic_route');
    $this->assertSame('/generic', $generic_route->getPath());
    $this->assertNull($generic_route->getOption('_auth'));

    $jsonapi_resource_route = $route_collection->get('jsonapi_resource_route');
    $this->assertSame('/jsonapi/resource', $jsonapi_resource_route->getPath());
    $this->assertSame(['GET'], $jsonapi_resource_route->getMethods());
    $this->assertSame(['basic_auth'], $jsonapi_resource_route->getOption('_auth'));

    $multi_method_route = $route_collection->get('jsonapi_resource_multi_method_route');
    $this->assertSame('/jsonapi/resource', $multi_method_route->getPath());
    $this->assertSame(['POST', 'PATCH'], $multi_method_route->getMethods());
    $this->assertSame(['basic_auth'], $multi_method_route->getOption('_auth'));
  }

}
