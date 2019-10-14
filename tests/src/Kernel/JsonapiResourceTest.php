<?php

namespace Drupal\Tests\jsonapi_resources\Kernel;

use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi_resources\Plugin\jsonapi_resources\JsonapiResourceInterface;
use Drupal\jsonapi_resources_test\Plugin\jsonapi_resources\FeaturedNodes;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

class JsonapiResourceTest extends EntityKernelTestBase {
  public static $modules = [
    'node',
    'serialization',
    'jsonapi',
    'jsonapi_resources',
    'jsonapi_resources_test',
  ];

  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  public function testDiscovery() {
    $manager = $this->container->get('plugin.manager.jsonapi_resource');
    $this->assertTrue($manager->hasDefinition('featured_nodes'));
  }

  public function testPluginResponse() {
    NodeType::create([
      'type' => 'article',
    ])->save();

    $manager = $this->container->get('plugin.manager.jsonapi_resource');
    $plugin = $manager->createInstance('featured_nodes');
    assert($plugin instanceof FeaturedNodes);

    $data = $plugin->get();
    $this->assertInstanceOf(ResourceObjectData::class, $data);
    $this->assertEquals(0, $data->count());

    Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'promote' => 1,
    ])->save();
    Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'promote' => 0,
    ])->save();

    $data = $plugin->get();
    $this->assertInstanceOf(ResourceObjectData::class, $data);
    $this->assertEquals(1, $data->count());
  }
}
