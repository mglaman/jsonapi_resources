<?php

namespace Drupal\Tests\jsonapi_resources\Unit;

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
use Drupal\jsonapi\Serializer\Serializer;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Drupal\jsonapi_resources\JsonapiResourceManagerInterface;
use Drupal\jsonapi_resources\JsonapiResourcePermissions;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\JsonapiResourceBase;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\JsonapiResourceInterface;
use Drupal\Tests\UnitTestCase;

class PermissionsTest extends UnitTestCase {

  /**
   * @dataProvider dataProviderJsonapiResources
   */
  public function testPermissions(JsonapiResourceInterface $jsonapi_resource, array $expected_permissions) {
    $mock_manager = $this->prophesize(JsonapiResourceManagerInterface::class);
    $mock_manager->getDefinitions()->willReturn([$jsonapi_resource->getPluginId() => $jsonapi_resource->getPluginDefinition()]);
    $mock_manager->createInstance($jsonapi_resource->getPluginId())->willReturn($jsonapi_resource);

    $permissions_handler = new JsonapiResourcePermissions($mock_manager->reveal());
    $this->assertEquals($expected_permissions, array_keys($permissions_handler->permissions()));

  }

  public function dataProviderJsonapiResources() {
    $resource_type_repository = $this->prophesize(ResourceTypeRepositoryInterface::class);
    $entity_resource_shim = $this->getMockedEntityResourceShim();
    yield [
      new class([], 'test_resource', [
        'id' => 'test_resource',
        'label' => 'Test Resource',
        'uri_path' => '/test-resource',
      ], $resource_type_repository->reveal(), $entity_resource_shim) extends JsonapiResourceBase {
        public function get() {
        }
      },
      [
        'jsonapi_resources get test_resource',
      ]
    ];
    yield [
      new class([], 'test_resource', [
        'id' => 'test_resource',
        'label' => 'Test Resource',
        'uri_path' => '/test-resource',
      ], $resource_type_repository->reveal(), $entity_resource_shim) extends JsonapiResourceBase {
        public function get() {
        }
        public function post() {
        }
        public function patch() {
        }
        public function head() {
          // not a supported method, should not generate a permission.
        }
      },
      [
        'jsonapi_resources get test_resource',
        'jsonapi_resources post test_resource',
        'jsonapi_resources patch test_resource',
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