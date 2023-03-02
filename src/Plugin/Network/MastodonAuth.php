<?php

namespace Drupal\social_auth_mastodon\Plugin\Network;

use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth\Settings\SettingsInterface;
use Lrf141\OAuth2\Client\Provider\Mastodon;
use Drupal\social_auth\Plugin\Network\NetworkInterface;

/**
 * Defines a Network Plugin for Social Auth Mastodon.
 *
 * @package Drupal\social_auth_mastodon\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_mastodon",
 *   short_name = "mastodon",
 *   social_network = "Mastodon",
 *   type = "social_auth",
 *   class_name = "\League\OAuth2\Client\Provider\Mastodon",
 *   auth_manager = "\Drupal\social_auth_mastodon\MastodonAuthManager",
 *   routes = {
 *     "redirect": "social_auth.network.redirect",
 *     "callback": "social_auth.network.callback",
 *     "settings_form": "social_auth.network.settings_form",
 *   },
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_mastodon\Settings\MastodonAuthSettings",
 *       "config_id": "social_auth_mastodon.settings"
 *     }
 *   }
 * )
 */
class MastodonAuth extends NetworkBase implements NetworkInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Lrf141\OAuth2\Client\Provider\Mastodon|false
   *   The initialized 3rd party library instance.
   *   False if could not be initialized.
   *
   * @throws \Drupal\social_api\SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk(): mixed {

    $class_name = '\Lrf141\OAuth2\Client\Provider\Mastodon';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Mastodon Library for the OAuth2 not found. Class: %s.', $class_name));
    }

    /** @var \Drupal\social_auth_mastodon\Settings\MastodonAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId'      => $settings->getClientId(),
        'clientSecret'  => $settings->getClientSecret(),
        'redirectUri'   => $this->getCallbackUrl()->setAbsolute()->toString(),
        'instance'      => $settings->getDefaultInstance() == '' ? NULL : $settings->getDefaultInstance(),
      ];

      // Proxy configuration data for outward proxy.
      $config = $this->siteSettings->get('http_client_config');
      if (!empty($config['proxy']['http'])) {
        $league_settings['proxy'] = $config['proxy']['http'];
      }

      return new Mastodon($league_settings);
    }

    return FALSE;
  }

}
