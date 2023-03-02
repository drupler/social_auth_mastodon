<?php

namespace Drupal\social_auth_mastodon\Settings;

/**
 * Defines an interface for Social Auth Mastodon settings.
 */
interface MastodonAuthSettingsInterface {

  /**
   * Gets the default instance.
   *
   * @return string|null
   *   The default instance.
   */
  public function getDefaultInstance(): ?string;

}
