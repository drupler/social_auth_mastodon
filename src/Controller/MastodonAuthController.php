<?php

namespace Drupal\social_auth_mastodon\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_mastodon\MastodonAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Auth Mastodon module routes.
 */
class MastodonAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $userManager;

  /**
   * The Mastodon authentication manager.
   *
   * @var \Drupal\social_auth_mastodon\MastodonAuthManager
   */
  private $mastodonManager;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  private $dataHandler;

  /**
   * MastodonAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_mastodon network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_mastodon\MastodonAuthManager $mastodon_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $data_handler
   *   SocialAuthDataHandler object.
   */
  public function __construct(NetworkManager $network_manager,
                              SocialAuthUserManager $user_manager,
                              MastodonAuthManager $mastodon_manager,
                              RequestStack $request,
                              SocialAuthDataHandler $data_handler) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->mastodonManager = $mastodon_manager;
    $this->request = $request;
    $this->dataHandler = $data_handler;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_mastodon');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['access_token', 'oauth2state']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_mastodon.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler')
    );
  }

  /**
   * Response for path 'user/login/mastodon'.
   *
   * Redirects the user to Mastodon for authentication.
   */
  public function redirectToMastodon() {
    /* @var \Lrf141\OAuth2\Client\Provider\Mastodon false $mastodon */
    $mastodon = $this->networkManager->createInstance('social_auth_mastodon')->getSdk();

    // If client could not be obtained.
    if (!$mastodon) {
      drupal_set_message($this->t('Social Auth Mastodon not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Destination parameter specified in url.
    $destination = $this->request->getCurrentRequest()->get('destination');
    // If destination parameter is set, save it.
    if ($destination) {
      $this->userManager->setDestination($destination);
    }

    // Mastodon service was returned, inject it to $mastodonManager.
    $this->mastodonManager->setClient($mastodon);

    // Generates the URL where the user will be redirected for Mastodon login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $mastodon_login_url = $this->mastodonManager->getAuthorizationUrl();

    $state = $this->mastodonManager->getState();

    $this->dataHandler->set('oauth2state', $state);

    return new TrustedRedirectResponse($mastodon_login_url);
  }

  /**
   * Response for path 'user/login/mastodon/callback'.
   *
   * Mastodon returns the user here after user has authenticated in Mastodon.
   */
  public function callback() {
    // Checks if user cancel login via Mastodon.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \Lrf141\OAuth2\Client\Provider\Mastodon|false $mastodon */
    $mastodon = $this->networkManager->createInstance('social_auth_mastodon')->getSdk();

    // If Mastodon client could not be obtained.
    if (!$mastodon) {
      drupal_set_message($this->t('Social Auth Mastodon not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oauth2state');

    // Retrieves $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      drupal_set_message($this->t('Mastodon login failed. Unvalid OAuth2 state.'), 'error');
      return $this->redirect('user.login');
    }

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->mastodonManager->getAccessToken());

    $this->mastodonManager->setClient($mastodon)->authenticate();

    // Gets user's info from Mastodon API.
    if (!$mastodon_profile = $this->mastodonManager->getUserInfo()) {
      drupal_set_message($this->t('Mastodon login failed, could not load Mastodon profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Gets (or not) extra initial data.
    $data = $this->userManager->checkIfUserExists($mastodon_profile->getId()) ? NULL : $this->mastodonManager->getExtraDetails();

    // If user information could be retrieved.
    return $this->userManager->authenticateUser(
      $mastodon_profile->getName(),
      NULL, // User's e-mail address is not possible to check.
      $mastodon_profile->getId(),
      $this->mastodonManager->getAccessToken(),
      $mastodon_profile->toArray()['avatar'],
      $data
    );
  }

}
