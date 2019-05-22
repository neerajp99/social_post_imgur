<?php

namespace Drupal\social_post_imgur\Plugin\Network;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
use Drupal\social_post_imgur\Settings\ImgurPostSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
/**
 * Calls the AdamPaterson client for Oauth2-Imgur.
 */
use \AdamPaterson\OAuth2\Client\Provider\Imgur;


class ImgurPost extends SocialPostNetwork {

  use LoggerChannelTrait;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Imgur connection.
   *
   * @var \League\OAuth2\Client\Provider\ImgurOAuth
   */
  protected $connection;

  /**
   * The Post text.
   *
   * @var string
   */
  protected $status;

  /**
   * The logger factory object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */

  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('url_generator'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * ImgurPost constructor.
   *
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   Used to generate a absolute url for authentication.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   Used for logging errors.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator,
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory,
                              LoggerChannelFactory $logger_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Sets the underlying SDK library.
   *
   * @return \League\OAuth2\Client\Provider\Imgur
   *   The initialized 3rd party library instance.
   *
   * @throws SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = '\AdamPaterson\OAuth2\Client\Provider\Imgur';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Imgur Library for the league oAuth not found. Class: %s.', $class_name));
    }
    /* @var \Drupal\social_auth_imgur\Settings\ImgurAuthSettings $settings */
    $settings = $this->settings;
    if ($this->validateConfig($settings)) {
      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => $GLOBALS['base_url']  . '/user/social-post/imgur/auth/callback',
      ];
      return new Imgur($league_settings);
    }
    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_post_imgur\Settings\ImgurPostSettings $settings
   *   The Imgur auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(ImgurPostSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_imgur')
        ->error('Define Client ID and Client Secret on module settings.');
      return FALSE;
    }

    return TRUE;
  }

}
