<?php

namespace Drupal\social_post_imgur;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\social_post\PostManager;

/**
 * Manages the authorization process and post on user behalf.
 */
class ImgurPostAuthManager extends PostManager\PostManager {
  /**
   * The session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $session;

  /**
   * The Imgur client object.
   *
   * @var \League\OAuth2\Client\Provider\Imgur
   */
  protected $client;

  /**
   * The HTTP client object.
   *
   * @var \League\OAuth2\Client\Provider\Imgur
   */
  protected $httpClient;


  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Imgur access token.
   *
   * @var \League\OAuth2\Client\Token\AccessToken
   */
  protected $token;

  /**
   * ImgurPostManager constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The session manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to get the parameter code returned by Imgur.
   */
  public function __construct(Session $session, RequestStack $request) {
    $this->session = $session;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * Saves access token.
   */
  public function authenticate() {
    $this->token = $this->client->getAccessToken('authorization_code',
      ['code' => $_GET['code']]);
  }

  /**
   * Returns the Imgur login URL where user will be redirected.
   *
   * @return string
   *   Absolute Imgur login URL where user will be redirected
   */
  public function getImgurLoginUrl() {
    $scopes = [];

    $login_url = $this->client->getAuthorizationUrl([
      'scope' => $scopes,
    ]);
    // Generate and return the URL where we should redirect the user.
    return $login_url;
  }

  /**
   * Gets the data by using the access token returned.
   *
   * @return League\OAuth2\Client\Provider\ImgurUser
   *   User Info returned by the imgur.
   */
  public function getUserInfo() {
    $this->user = $this->client->getResourceOwner($this->token);

    var_dump($this->user->getName());
    return $this->user;
  }

  /**
   * Returns token generated after authorization.
   *
   * @return string
   *   Used for making API calls.
   */
  public function getAccessToken() {
    return $this->token;
  }

  /**
   * Makes an API call to imgur server.
   */
  public function requestApiCall($message, $token, $userId) {
    $post = [
      'text' => $message,
      'token' => $token,
      'Authorization' => 'Client-ID' . $userId
    ];

    $ch = curl_init('https://api.imgur.com/3/image');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    // execute!
    curl_exec($ch);

    // Close the connection, release resources used.
    curl_close($ch);

    // $curl = curl_init();
    //
    // curl_setopt_array($curl, array(
    //   CURLOPT_URL => "https://api.imgur.com/3/image",
    //   CURLOPT_RETURNTRANSFER => true,
    //   CURLOPT_ENCODING => "",
    //   CURLOPT_MAXREDIRS => 10,
    //   CURLOPT_TIMEOUT => 0,
    //   CURLOPT_FOLLOWLOCATION => false,
    //   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //   CURLOPT_CUSTOMREQUEST => "POST",
    //   CURLOPT_POSTFIELDS => array('image' => ''),
    //   CURLOPT_HTTPHEADER => array(
    //     "Authorization: Client-ID {{clientId}}"
    //   ),
    // ));

    // $response = curl_exec($curl);
    // $err = curl_error($curl);
    //
    // curl_close($curl);

  }

  /**
   * Returns the Imgur login URL where user will be redirected.
   *
   * @return string
   *   Absolute Imgur login URL where user will be redirected
   */
  public function getState() {
    $state = $this->client->getState();

    // Generate and return the URL where we should redirect the user.
    return $state;
  }

}
