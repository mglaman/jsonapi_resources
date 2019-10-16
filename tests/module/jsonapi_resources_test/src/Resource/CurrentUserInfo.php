<?php

namespace Drupal\jsonapi_resources_test\Resource;

use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_resources\Resource\EntityResourceBase;
use Drupal\jsonapi_resources\ResourceResponseFactory;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class CurrentUserInfo extends EntityResourceBase {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $tokenGenerator;

  /**
   * Constructs a new EntityResourceBase object.
   *
   * @param \Drupal\jsonapi_resources\ResourceResponseFactory $resource_response_factory
   *   The resource response factory.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Tne entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $token_generator
   *   The CSRF token generator.
   */
  public function __construct(ResourceResponseFactory $resource_response_factory, ResourceTypeRepositoryInterface $resource_type_repository, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, CsrfTokenGenerator $token_generator) {
    parent::__construct($resource_response_factory, $resource_type_repository, $entity_type_manager);
    $this->currentUser = $account;
    $this->tokenGenerator = $token_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi_resource.resource_response_factory'),
      $container->get('jsonapi.resource_type.repository'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('csrf_token')
    );
  }

  /**
   * Process the resource request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function process(Request $request) {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $current_user = $user_storage->load($this->currentUser->id());
    assert($current_user instanceof UserInterface);
    $resource_type = new ResourceType('current_user', 'current_user', NULL);
    $resource_type->setRelatableResourceTypes([]);

    $links = new LinkCollection([]);
    $primary_data = new ResourceObject(
      $current_user,
      $resource_type,
      $current_user->uuid(),
      NULL,
      [
        'displayName' => $current_user->getDisplayName(),
        'roles' => $current_user->getRoles(TRUE),
        'token' => $this->tokenGenerator->get(CsrfRequestHeaderAccessCheck::TOKEN_KEY),
      ],
      $links
    );
    $top_level_data = new ResourceObjectData([$primary_data]);
    $response = $this->resourceResponseFactory->create($top_level_data, $request);
    $response->addCacheableDependency($current_user);
    return $response;
  }

}
