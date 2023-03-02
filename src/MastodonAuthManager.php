<?php

namespace Drupal\social_auth_mastodon;

use Drupal\social_auth\AuthManager\OAuth2Manager;
use Drupal\social_auth\User\SocialAuthUser;
use Drupal\social_auth\User\SocialAuthUserInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains all the logic for Mastodon OAuth2 authentication.
 */
class MastodonAuthManager extends OAuth2Manager {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Used for accessing configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Used to get the authorization code from the callback request.vvvvv
   */
  public function __construct(ConfigFactory $config_factory,
                              LoggerChannelFactoryInterface $logger_factory,
                              RequestStack $request_stack) {
    parent::__construct($config_factory->get('social_auth_mastodon.settings'),
                        $logger_factory,
                        $request_stack->getCurrentRequest());
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(): void {
    try {
      $this->setAccessToken($this->client->getAccessToken('authorization_code',
        ['code' => $_GET['code']]));
    }
    catch (IdentityProviderException $e) {
      $this->loggerFactory->get('social_auth_mastodon')
        ->error('There was an error during authentication. Exception: ' . $e->getMessage());
    }
  }

    /**
   * {@inheritdoc}
   */
  public function getUserInfo(): SocialAuthUserInterface {
    if (!$this->user) {
      $owner = $this->client->getResourceOwner($this->getAccessToken());
      $this->user = new SocialAuthUser(
        $owner->getName(),
        $owner->getId(),
        $this->getAccessToken(),
        $owner->getEmail(),
        $owner->getAvatar(),
        $this->getExtraDetails()
      );
    }
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl(): string {
    $scopes = ['read', 'write'];

    $extra_scopes = $this->getScopes();
    if ($extra_scopes) {
      $scopes = array_merge($scopes, explode(',', $extra_scopes));
    }

    // Returns the URL where user will be redirected.
    return $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint($method, $path, $domain = NULL, array $options = []): mixed {
    if (!$domain) {
      $domain = $this->client->getInstanceUrl();
    }

    $url = $domain . $path;

    try {
      $request = $this->client->getAuthenticatedRequest($method, $url, $this->getAccessToken(), $options);
    }
    catch (\Exception $e) {
      watchdog_exception('social_auth_mastodon', $e);
      return NULL;
    }

    $request = $this->client->getAuthenticatedRequest($method, $url, $this->getAccessToken());

    try {
      return $this->client->getParsedResponse($request);
    }
    catch (IdentityProviderException $e) {
      $this->loggerFactory->get('social_auth_mastodon')
        ->error('There was an error when requesting ' . $url . '. Exception: ' . $e->getMessage());
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getState(): string {
    return $this->client->getState();
  }

}
