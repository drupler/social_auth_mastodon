<?php

namespace Drupal\social_auth_mastodon\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\social_api\Plugin\NetworkBase;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth_mastodon\Settings\MastodonAuthSettings;
use Lrf141\OAuth2\Client\Provider\Mastodon;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Network Plugin for Social Auth Mastodon.
 *
 * @package Drupal\social_auth_mastodon\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_mastodon",
 *   social_network = "Mastodon",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_mastodon\Settings\MastodonAuthSettings",
 *       "config_id": "social_auth_mastodon.settings"
 *     }
 *   }
 * )
 */
class MastodonAuth extends NetworkBase implements MastodonAuthInterface {

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $siteSettings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('settings')
    );
  }

  /**
   * MastodonAuth constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              array $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
                              LoggerChannelFactoryInterface $logger_factory,
                              Settings $settings) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->loggerFactory = $logger_factory;
    $this->siteSettings = $settings;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \Lrf141\OAuth2\Client\Provider\Mastodon|false
   *   The initialized 3rd party library instance.
   *   False if could not be initialized.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\Lrf141\OAuth2\Client\Provider\Mastodon';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Mastodon Library for the OAuth2 not found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_auth_mastodon\Settings\MastodonAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_mastodon.callback')->setAbsolute()->toString(),
        'instance' => $settings->getInstance(),
        'scope' => 'read:accounts',
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings = [
          'proxy' => $proxyUrl,
        ];
      }

      return new Mastodon($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_mastodon\Settings\MastodonAuthSettings $settings
   *   The Mastodon auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(MastodonAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_mastodon')
        ->error('Define Client ID and Client Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

}
