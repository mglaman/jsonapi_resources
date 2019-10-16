<?php declare(strict_types=1);

namespace Drupal\Tests\jsonapi_resources\Unit;

use Drupal\jsonapi_resources\Routing\Enhancer\ResourceEnhancer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\jsonapi_resources\Routing\Enhancer\ResourceEnhancer
 */
final class ResourceEnhancerTest extends UnitTestCase {

  /**
   */
  public function testEnhance() {
    $resource_enhancer = new ResourceEnhancer();

    $route_defaults = [
      '_jsonapi_resource' => '\\Drupal\\mymodule\\ResourceHandler::method',
    ];
    $enhanced_defaults = $resource_enhancer->enhance($route_defaults, Request::createFromGlobals());
    $this->assertSame($enhanced_defaults['_controller'], $route_defaults['_jsonapi_resource']);

    $route_defaults = [
      '_controller' => '\\Drupal\\mymodule\\ResourceHandler::method',
    ];
    $enhanced_defaults = $resource_enhancer->enhance($route_defaults, Request::createFromGlobals());
    $this->assertSame($enhanced_defaults['_controller'], $route_defaults['_controller']);
  }

}
