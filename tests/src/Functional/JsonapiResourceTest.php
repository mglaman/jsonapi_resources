<?php

namespace Drupal\Tests\jsonapi_resources\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\jsonapi\Functional\ResourceResponseTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use GuzzleHttp\RequestOptions;

class JsonapiResourceTest extends BrowserTestBase {

  use JsonApiRequestTestTrait;
  use ResourceResponseTestTrait;

  /**
   * The account to use for authentication.
   *
   * @var null|\Drupal\Core\Session\AccountInterface
   */
  protected $account;

  protected static $modules = [
    'basic_auth',
    'node',
    'path',
    'jsonapi_resources',
    'jsonapi_resources_test',
  ];

  protected function setUp() {
    parent::setUp();
    // Ensure the anonymous user role has no permissions at all.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The anonymous user role has no permissions at all.');

    // Ensure the authenticated user role has no permissions at all.
    $user_role = Role::load(RoleInterface::AUTHENTICATED_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The authenticated user role has no permissions at all.');

    // Create an account, which tests will use. Also ensure the @current_user
    // service this account, to ensure certain access check logic in tests works
    // as expected.
    $this->account = $this->createUser();
    $this->container->get('current_user')->setAccount($this->account);
  }

  // Tests a custom collection.
  public function testFeaturedNodesPlugin() {
    NodeType::create([
      'name' => 'article',
      'type' => 'article',
    ])->save();
    $this->container->get('router.builder')->rebuild();

    $promoted_nodes = [];
    for ($i = 0; $i < 8; $i++) {
      $promoted = ($i % 2 === 0);
      $node = Node::create([
        'type' => 'article',
        'title' => $this->randomString(),
        'status' => 1,
        'promote' => $promoted ? 1 : 0,
      ]);
      $node->save();
      if ($promoted) {
        $promoted_nodes[$node->uuid()] = $node;
      }
    }
    $this->grantPermissionsToTestedRole([
      'access content',
      'access user profiles',
    ]);

    $url = Url::fromRoute('jsonapi_resources_test.featured_content');
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    $response = $this->request('GET', $url, $request_options);

    $this->assertSame(200, $response->getStatusCode(), var_export(Json::decode((string) $response->getBody()), TRUE));
    $response_document = Json::decode((string) $response->getBody());

    $this->assertCount(4, $response_document['data']);
    $this->assertSame(array_keys($promoted_nodes), array_map(static function (array $data) {
      return $data['id'];
    }, $response_document['data']));
  }

  // Tests a custom collection with custom route parameter.
  public function testAuthorContentPlugin() {
    NodeType::create([
      'name' => 'article',
      'type' => 'article',
    ])->save();
    $this->container->get('router.builder')->rebuild();

    $author_user = $this->createUser();
    $node1 = Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'uid' => $author_user->id(),
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'title' => $this->randomString(),
      'status' => 1,
      'uid' => $author_user->id(),
    ]);
    $node2->save();

    $this->grantPermissionsToTestedRole([
      'access content',
    ]);

    $url = Url::fromRoute('jsonapi_resources_test.author_content', [
      'user' => $author_user->id(),
    ]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    $response = $this->request('GET', $url, $request_options);

    $this->assertSame(200, $response->getStatusCode(), var_export(Json::decode((string) $response->getBody()), TRUE));
    $response_document = Json::decode((string) $response->getBody());
    $this->assertCount(2, $response_document['data']);
    $this->assertArrayHasKey('included', $response_document);
    $this->assertNotEmpty($response_document['included']);
  }

  /**
   * Grants permissions to the authenticated role.
   *
   * @param string[] $permissions
   *   Permissions to grant.
   */
  protected function grantPermissionsToTestedRole(array $permissions) {
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), $permissions);
  }

  /**
   * Returns Guzzle request options for authentication.
   *
   * @return array
   *   Guzzle request options to use for authentication.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getAuthenticationRequestOptions() {
    return [
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->account->name->value . ':' . $this->account->passRaw),
      ],
    ];
  }

}
