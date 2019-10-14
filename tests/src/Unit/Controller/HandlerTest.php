<?php

namespace Drupal\Tests\jsonapi_resources\Unit\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Serializer\Serializer;
use Drupal\jsonapi_resources\Controller\Handler;
use Drupal\jsonapi_resources\Controller\jsonapi\EntityResourceShim;
use Drupal\jsonapi_resources\JsonapiResourceManagerInterface;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\JsonapiResourceBase;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;

class HandlerTest extends UnitTestCase {

  public function testPluginDoesNotExist() {
    $manager = $this->prophesize(JsonapiResourceManagerInterface::class);
    $manager->hasDefinition('test_resource')->willReturn(FALSE);
    $controller = new Handler(
      $this->prophesize(ResourceTypeRepositoryInterface::class)->reveal(),
      new Serializer(),
      $manager->reveal(),
      $this->getMockedEntityResourceShim()
    );
    $this->expectException(AccessDeniedHttpException::class);
    $this->expectExceptionMessage('test_resource does not exist');
    $controller->handle(
      $this->prophesize(RouteMatchInterface::class)->reveal(),
      Request::createFromGlobals(),
      'test_resource'
    );
  }

  public function testPluginDoesNotHandleMethod() {
    $entity_resource_shim = $this->getMockedEntityResourceShim();
    $resource_type_repository = $this->prophesize(ResourceTypeRepositoryInterface::class);

    $manager = $this->prophesize(JsonapiResourceManagerInterface::class);
    $manager->hasDefinition('test_resource')->willReturn(TRUE);
    $manager->createInstance('test_resource')->willReturn(new class([], 'test_resource', [
      'id' => 'test_resource',
      'label' => 'Test Resource',
      'uri_path' => '/test-resource',
    ], $resource_type_repository->reveal(), $entity_resource_shim) extends JsonapiResourceBase {
      public function get() {
        return new ResourceResponse(NULL);
      }
    });

    $controller = new Handler(
      $this->prophesize(ResourceTypeRepositoryInterface::class)->reveal(),
      new Serializer(),
      $manager->reveal(),
      $entity_resource_shim
    );

    $this->expectException(AccessDeniedHttpException::class);
    $this->expectExceptionMessage('test_resource does not handle POST requests.');

    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route = $this->prophesize(Route::class);
    $route->getMethods()->willReturn(['POST']);
    $route_match->getRouteObject()->willReturn($route);

    $controller->handle(
      $route_match->reveal(),
      Request::create('https://example.com/jsonapi/test-resource', 'POST'),
      'test_resource'
    );
  }

  public function testPluginHandleMethod() {
    $entity_resource_shim = $this->getMockedEntityResourceShim();
    $resource_type_repository = $this->prophesize(ResourceTypeRepositoryInterface::class);

    $manager = $this->prophesize(JsonapiResourceManagerInterface::class);
    $manager->hasDefinition('test_resource')->willReturn(TRUE);
    $manager->createInstance('test_resource')->willReturn(new class([], 'test_resource', [
      'id' => 'test_resource',
      'label' => 'Test Resource',
      'uri_path' => '/test-resource',
    ], $resource_type_repository->reveal(), $entity_resource_shim) extends JsonapiResourceBase {
      public function get() {
        return new ResourceResponse(NULL);
      }
    });

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens(Argument::any())->willReturn(TRUE);
    $url_assembler = $this->prophesize(UnroutedUrlAssemblerInterface::class);
    $url_assembler->assemble(
      'https://example.com/jsonapi/test-resource',
      ['external' => TRUE, 'absolute' => TRUE],
      TRUE
    )->willReturn((new GeneratedUrl())->setGeneratedUrl('https://example.org/'));
    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $url_assembler->reveal());
    $container->set('cache_contexts_manager', $cache_contexts_manager->reveal());
    \Drupal::setContainer($container);

    $controller = new Handler(
      $this->prophesize(ResourceTypeRepositoryInterface::class)->reveal(),
      new Serializer(),
      $manager->reveal(),
      $entity_resource_shim
    );

    $route_match = $this->prophesize(RouteMatchInterface::class);
    $route = $this->prophesize(Route::class);
    $route->getMethods()->willReturn(['GET']);
    $route_match->getRouteObject()->willReturn($route);

    $request = Request::create('https://example.com/jsonapi/test-resource');
    $response = $controller->handle(
      $route_match->reveal(),
      $request,
      'test_resource'
    );
    $this->assertInstanceOf(ResourceResponse::class, $response);
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
