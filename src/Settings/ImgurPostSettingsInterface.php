<?php

namespace Drupal\social_post_imgur\Settings;

/**
 * Defines an interface for Social Post Imgur settings.
 */
interface ImgurPostSettingsInterface {

  /**
   * Gets the application ID.
   *
   * @return mixed
   *   The application ID.
   */
  public function getClientId();

  /**
   * Gets the application secret.
   *
   * @return string
   *   The application secret.
   */
  public function getClientSecret();

}
