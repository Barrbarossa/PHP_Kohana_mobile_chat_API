<?php
defined('SYSPATH') or die('No direct script access.');

class Authorization_Facebook extends Singleton implements ISocialAuthorization
{

    protected $app_id;
    protected $app_secret;
    protected $redirect_url;
    //models
    private $account_model;
    private $profile_model;
    private $session_model;

    public function __construct()
    {
        $facebook_settings = Kohana::$config->load('authorization')->get('facebook');
        $this->app_id = $facebook_settings['app_id'];
        $this->app_secret = $facebook_settings['app_secret'];
        $this->redirect_url = $facebook_settings['redirect_url'];
        $this->account_model = ORM::factory('Account');
        $this->profile_model = ORM::factory('Profile');
        $this->session_model = ORM::factory('Session');
    }

    public function login($user_id, $access_token)
    {
        $url = 'https://graph.facebook.com/' . $user_id . '?access_token=' . $access_token;
        $request = Request::factory($url);

        $request->client()->options(array(
            CURLOPT_SSL_VERIFYPEER => FALSE
        ));
        $response = json_decode($request->execute()->body());
        if (!@$response->error) {
            $account = $this->profile_model->getAccountByFacebookId($user_id);
            $session = $this->session_model->getSessionByUserId($user_id);
            if (!$account) {
                    $data = array("profile" => array(
                        "gender" => isset($response->gender) ? $response->gender : "",
                        "firstname" => isset($response->first_name) ? $response->first_name : "",
                        "lastname" => isset($response->last_name) ? $response->last_name : "",
                        "facebook_id" => isset($response->id) ? $response->id : ""
                    ),
                        'login' => '',
                        'password' => '',
                        'username' => '',
                        'latitude' => '',
                        'longitude' => ''
                    );
                    if (($account_id = $this->account_model->_create($data))) {
                        $this->account_model->clear();
                        $account_info = $this->account_model->getAccountInfo($account_id);
                        return array('code' => '0', 'data' => array('userID' => $account_info->userID,
                            'login' => $account_info->login,
                            'username' => $account_info->username,
                            'latitude' => $account_info->latitude,
                            'longitude' => $account_info->longitude,
                            'profile' => array(
                                'facebook_id' => $account_info->profile->facebook_id,
                                'vk_id' => $account_info->profile->vk_id,
                                'twitter_id' => $account_info->profile->twitter_id,
                                'firstname' => $account_info->profile->firstname,
                                'lastname' => $account_info->profile->lastname,
                                'gender' => $account_info->profile->gender,
                                'account_id' => $account_info->profile->account_id
                            ),
                            'SSID' => $session->SSID
                    ));
                    } else {
                        return array('code' => '1', 'data' => 'error registration');
                    }
                } else {
                    return array('code' => '0', 'data' => array('userID' => $account->userID,
                        'login' => $account->login,
                        'username' => $account->username,
                        'latitude' => $account->latitude,
                        'longitude' => $account->longitude,
                        'profile' => array(
                            'facebook_id' => $account->profile->facebook_id,
                            'vk_id' => $account->profile->vk_id,
                            'twitter_id' => $account->profile->twitter_id,
                            'firstname' => $account->profile->firstname,
                            'lastname' => $account->profile->lastname,
                            'gender' => $account->profile->gender,
                            'account_id' => $account->profile->account_id
                        ),
                        'SSID' => $session->SSID
                ));
                }
            } else {
                return array('code' => '1', 'data' => $response->error->message);
            }

    }

    public function get($data)
    {

    }

}