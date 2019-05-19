<?php

namespace Drupal\social_post_imgur\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_post\SocialPostDataHandler;
use Drupal\social_post\SocialPostManager;
use Drupal\social_post_imgur\ImgurPostAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple Imgur Connect module routes.
 */
class ImgurPostController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The Imgur authentication manager.
   *
   * @var \Drupal\social_post_imgur\ImgurPostAuthManager
   */
  private $imgurManager;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_post\SocialPostDataHandler
   */
  private $dataHandler;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The social post manager.
   *
   * @var \Drupal\social_post\SocialPostManager
   */
  protected $postManager;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * ImgurAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_post_imgur network plugin.
   * @param \Drupal\social_post\SocialPostManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_post_imgur\ImgurPostAuthManager $imgur_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_post\SocialPostDataHandler $data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(NetworkManager $network_manager,
                              SocialPostManager $user_manager,
                              ImgurPostAuthManager $imgur_manager,
                              RequestStack $request,
                              SocialPostDataHandler $data_handler,
                              LoggerChannelFactoryInterface $logger_factory,
                              MessengerInterface $messenger) {

    $this->networkManager = $network_manager;
    $this->postManager = $user_manager;
    $this->imgurManager = $imgur_manager;
    $this->request = $request;
    $this->dataHandler = $data_handler;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;

    $this->postManager->setPluginId('social_post_imgur');

    // Sets session prefix for data handler.
    $this->dataHandler->setSessionPrefix('social_post_imgur');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_post.post_manager'),
      $container->get('imgur_post.auth_manager'),
      $container->get('request_stack'),
      $container->get('social_post.data_handler'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * Redirects the user to Imgur for authentication.
   */
  public function redirectToImgur() {
    /* @var \League\OAuth2\Client\Provider\Imgur|false $imgur */
    $imgur = $this->networkManager->createInstance('social_post_imgur')->getSdk();

    // If Imgur client could not be obtained.
    if (!$imgur) {
      $this->messenger->addError($this->t('Social Post Imgur not configured properly. Contact site administrator.'));
      return $this->redirect('user.login');
    }

    // Imgur service was returned, inject it to $imgurManager.
    $this->imgurManager->setClient($imgur);

    // Generates the URL where the user will be redirected for Imgur login.
    $imgur_login_url = $this->imgurManager->getImgurUrl();

    $state = $this->imgurManager->getState();

    $this->dataHandler->set('oAuth2state', $state);

    return new TrustedRedirectResponse($imgur_login_url);
    $response->send();
  }

  /**
   * Response for path 'user/login/imgur/callback'.
   *
   * Imgur returns the user here after user has authenticated in Imgur.
   */
   public function callback() {
   // Checks if user cancel login via Imgur.
   $error = $this->request->getCurrentRequest()->get('error');
   if ($error == 'access_denied') {
     drupal_set_message($this->t('You could not be authenticated.'), 'error');
     return $this->redirect('user.login');
   }

   /* @var \League\OAuth2\Client\Provider\Imgur false $imgur */
   $imgur = $this->networkManager->createInstance('social_post_imgur')->getSdk();

   // If imgur client could not be obtained.
   if (!$imgur) {
     drupal_set_message($this->t('Social Auth Imgur not configured properly. Contact site administrator.'), 'error');
     return $this->redirect('user.login');
   }

   $state = $this->dataHandler->get('oAuth2State');

   // Retrieves $_GET['state'].
   $retrievedState = $this->request->getCurrentRequest()->query->get('state');

   $this->imgurManager->setClient($imgur)->authenticate();

   if (!$imgur_profile = $this->imgurManager->getUserInfo()) {
     drupal_set_message($this->t('Imgur login failed, could not load Imgur profile. Contact site administrator.'), 'error');
     return $this->redirect('user.login');
   }
   if (!$this->postManager->checkIfUserExists($this->imgurManager->getUserInfo()->getId())) {
     $this->postManager->addRecord('social_post_imgur', $this->imgurManager->getUserInfo()->getId(), $this->imgurManager->getAccessToken(), $this->imgurManager->getUserInfo()->getName(), '');
   }
   return $this->redirect('entity.user.edit_form', ['user' => $this->postManager->getCurrentUser()]);
 }

}
