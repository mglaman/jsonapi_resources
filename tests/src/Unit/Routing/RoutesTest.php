<?php

namespace Drupal\Tests\jsonapi_resources\Unit\Rounting;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Routing\Routes as JsonapiRoutes;
use Drupal\jsonapi\Serializer\Serializer;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Drupal\jsonapi_resources\JsonapiResourceManagerInterface;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\ResourceBase;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\ResourceInterface;
use Drupal\jsonapi_resources\Routing\JsonapiResourceRoutes;
use Drupal\Tests\UnitTestCase;

class RoutesTest extends UnitTestCase {

  /**
   * @dataProvider dataProviderJsonapiResources
   */
  public function testRoutesCollection(ResourceInterface $jsonapi_resource, array $expected_routes) {
    $mock_manager = $this->prophesize(JsonapiResourceManagerInterface::class);
    $mock_manager->getDefinitions()->willReturn([$jsonapi_resource->getPluginId() => $jsonapi_resource->getPluginDefinition()]);
    $mock_manager->createInstance($jsonapi_resource->getPluginId())->willReturn($jsonapi_resource);
    $route_provider = new JsonapiResourceRoutes(
      $mock_manager->reveal(),
      ['cookie' => 'cookie'],
      '/jsonapi'
    );

    $routes_collection = $route_provider->routes();
    $this->assertCount($routes_collection->count(), $expected_routes);
    foreach ($expected_routes as $expected_route_name => $expected_route_definitions) {
      $route = $routes_collection->get($expected_route_name);
      $this->assertNotNull($route, "$expected_route_name was not in the RouteCollection.");

      $this->assertEquals($jsonapi_resource->getPluginId(), $route->getDefault('_jsonapi_resource'));

      $this->assertStringStartsWith('/jsonapi', $route->getPath());

      $this->assertEquals('api_json', $route->getRequirement('_content_type_format'));
      $this->assertEquals(['cookie'], $route->getOption('_auth'));
      $this->assertTrue($route->getDefault(JsonapiRoutes::JSON_API_ROUTE_FLAG_KEY));
      $this->assertEquals('api_json', $route->getRequirement('_format'));

      list($namespace, $plugin_id, $method) = explode('.', $expected_route_name);
      $lowered_method = strtolower($method);
      $this->assertEquals("jsonapi_resources $lowered_method {$jsonapi_resource->getPluginId()}", $route->getRequirement('_permission'));
    }
  }

  public function dataProviderJsonapiResources() {
    $resource_type_repository = $this->prophesize(ResourceTypeRepositoryInterface::class);
    $entity_resource_shim = $this->getMockedEntityResourceShim();
    yield [
      new class([], 'test_resource', [
        'id' => 'test_resource',
        'label' => 'Test Resource',
        'uri_path' => '/test-resource',
      ], $resource_type_repository->reveal(), $entity_resource_shim) extends ResourceBase {
        public function get() {
        }
      },
      [
        'jsonapi_resources.test_resource.GET' => [],
      ]
    ];
    yield [
      new class([], 'test_resource', [
        'id' => 'test_resource',
        'label' => 'Test Resource',
        'uri_path' => '/test-resource',
      ], $resource_type_repository->reveal(), $entity_resource_shim) extends ResourceBase {
        public function get() {
        }
        public function post() {
        }
        public function patch() {
        }
        public function head() {
          // not a supported method, should not generate a route.
        }
      },
      [
        'jsonapi_resources.test_resource.GET' => [],
        'jsonapi_resources.test_resource.POST' => [],
        'jsonapi_resources.test_resource.PATCH' => [],
      ]
    ];
  }

  private function getMockedEntityResourceShim() {
    return new EntityResourceShim(
      $this->prophesize(EntityTypeManagerInterface::class)->reveal(),
      $this->prophesize(EntityFieldManagerInterface::class)->reveal(),
      $this->prophesize(ResourceTypeRepositoryInterface::class)->reveal(),
      $this->prophesize(RendererInterface::class)->reveal(),
      $this->prophesize(EntityRepositoryInterface::class)->reveal(),
      $this->prophesize(IncludeResolver::class)->reveal(),
      $this->prophesize(EntityAccessChecker::class)->reveal(),
      $this->prophesize(FieldResolver::class)->reveal(),
      new Serializer(),
      $this->prophesize(TimeInterface::class)->reveal(),
      $this->prophesize(AccountInterface::class)->reveal()
    );
  }

}
