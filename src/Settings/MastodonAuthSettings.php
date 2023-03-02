<?php

namespace Drupal\social_auth_mastodon\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Defines methods to get Social Auth Mastodon settings.
 */
class MastodonAuthSettings extends SettingsBase implements MastodonAuthSettingsInterface {

  /**
   * Default instance URL.
   *
   * @var string|null
   */
  protected ?string $instance = NULL;


  /**
   * {@inheritdoc}
   */
  public function getDefaultInstance(): ?string  {
    if (!$this->instance) {
      $this->instance = $this->config->get('instance');
    }
    return $this->instance;
  }

}
