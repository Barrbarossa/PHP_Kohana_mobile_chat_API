<?php
defined('SYSPATH') or die('No direct script access.');

class Authorization_Google extends Singleton implements ISocialAuthorization {
    
    protected $api_key;
    protected $api_url;
    //models
    private $account_model;
    private $profile_model;
    private $session_model;
    
    public function __construct() {
        $google_settings = Kohana::$config->load('authorization')->get('google');
        $this->api_key = $google_settings['api_key'];
        $this->api_url = $google_settings['api_url'];
        $this->account_model = ORM::factory('Account');
        $this->profile_model = ORM::factory('Profile');
        $this->session_model = ORM::factory('Session');
    }

    public function login($user_id, $access_token = null) {
        $url = $this->api_url.$user_id.'?key='.$this->api_key;
        $request = Request::factory($url);

        $request->client()->options(array(
            CURLOPT_SSL_VERIFYPEER => FALSE
        ));
        $response = json_decode(json_decode($request->execute()->body()));
        if (!@$response->error) {
            $account = $this->profile_model->getAccountByGoogleId($user_id);
            $session = $this->session_model->getSessionByUserId($user_id);
            if (!$account) {
                $data = array("profile" => array(
                        "gender" => '',
                        "firstname" => $response->name->familyName,
                        "lastname" => $response->name->givenName,
                        "google_id" => $response->id
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
                            'SSID' => $session->SSID));
                } else {
                    return array('code' => '1', 'data' => 'error registration');
                }
            } else {
                return array('code' => '0', 'data' => array('userID' => $account->userID,
                        'login' => $account->login,
                        'username' => $account->username,
                        'latitude' => $account->latitude,
                        'longitude' => $account->longitude,
                        'SSID' => $session->SSID));
            }
        } else {
            return array('code' => '1', 'data' => $response->error->message);
        }
    }
    
    public function get($data) {
        
    }
}

?>
