<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Baas extends Controller
{

    private $account_model;
    private $session_model;
    private $devicetoken_model;

    public function before()
    {
        $this->account_model = ORM::factory('Account');
        $this->session_model = ORM::factory('Session');
        $this->devicetoken_model = ORM::factory('DeviceToken');
        parent::before();
    }

    public function action_index()
    {
        $this->response->body(array('msg' => 'index', 'code' => '10001'));
    }

    public function action_registration()
    {
        if ($this->request->jpost('login') && $this->request->jpost('password')) {
            $login = $this->request->jpost('login');
            $password = $this->request->jpost('password');
            $username = ($this->request->jpost('username')) ? $this->request->jpost('username') : '';
            $latitude = ($this->request->jpost('latitude')) ? $this->request->jpost('latitude') : '';
            $longitude = ($this->request->jpost('longitude')) ? $this->request->jpost('longitude') : '';

            $password_hash = $this->account_model->cryptPassword($password);
            if (!$this->account_model->existsAccount($login)) {
                $data = array('login' => $login, 'password' => $password_hash, 'username' => $username, 'latitude' => $latitude, 'longitude' => $longitude);
                if ($this->account_model->_create($data)) {
                    session_start();
                    $session_id = session_id();
                    $this->session_model->saveSession($this->account_model->userID, $session_id);
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $session_id));
                } else {
                    $this->response->body(array('msg' => 'error', 'code' => '00001'));
                }
            } else {
                $this->response->body(array('msg' => 'account exists', 'code' => '00002'));
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_authorization()
    {
        if ($this->request->jpost('login') && $this->request->jpost('password')) {
            $login = $this->request->jpost('login');
            $password = $this->request->jpost('password');
            $account = $this->account_model->getAccountByLogin($login);
            if (!$account->loaded()) {
                $this->response->body(array('msg' => 'account not found', 'code' => '00005'));
            } else {
                if ($this->account_model->equalPassword($password, $account->password)) {
                    $session = $this->session_model->getSessionByUserId($account->userID);
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $session->SSID));
                }
            }
        } else {
            //Anonymous user
            session_start();
            $session_id = session_id();
            $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $session_id));
        }
    }

    public function action_addDeviceToken()
    {
        if ($this->request->jpost('platformID') && $this->request->jpost('debugFlag') && $this->request->jpost('token') && $this->request->jpost('SSID')) {
            $platformID = $this->request->jpost('platformID');
            $debugFlag = $this->request->jpost('debugFlag');
            $token = $this->request->jpost('token');
            $SSID = $this->request->jpost('SSID');
            $session = $this->session_model->getSessionById($SSID);
            if ($session->loaded()) {
                $user_id = $session->userID;
                if ($this->devicetoken_model->is_Exist($platformID, $debugFlag, $token, $user_id) === FALSE) {
                    $this->devicetoken_model->saveToken($platformID, $debugFlag, $token, $user_id);
                    $this->response->body(array('msg' => 'success', 'code' => '10001'));
                } else {
                    $this->response->body(array('msg' => 'token exists', 'code' => '00007'));
                }
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_getUsers()
    {
        if ($this->request->jpost('SSID')) {
            $ownerID = $this->session_model->getUserIdBySession($this->request->jpost('SSID'));
            if ($ownerID == 0) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                $users = $this->account_model->getAll();
                $data = array();
                foreach ($users as $i => $user) {
                    $data[$i]['userID'] = $user->userID;
                    $data[$i]['login'] = $user->login;
                    $data[$i]['username'] = $user->username;
                    $data[$i]['latitude'] = $user->latitude;
                    $data[$i]['longitude'] = $user->longitude;
                    if ($user->userID == $ownerID) {
                        $data[$i]['current_user'] = 1;
                    }
                }
                $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $data));
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_setLocation()
    {
        if ($this->request->jpost("SSID") && $this->request->jpost("longitude") && $this->request->jpost("latitude")) {
            $ownerID = $this->session_model->getUserIdBySession($this->request->jpost('SSID'));
            if ($ownerID == 0)
                return $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));

            $latitude = $this->request->jpost("latitude");
            $longitude = $this->request->jpost("longitude");

            $this->account_model = ORM::factory("Account", $ownerID);

            $this->account_model->latitude = $latitude;
            $this->account_model->longitude = $longitude;

            $this->account_model->save();

            if($this->account_model->saved()){
                return $this->response->body(array('msg' => 'success', 'code' => '10001', "data" => $this->request->jpost("SSID")));
            } else {
                return $this->response->body(array('msg' => 'error', 'code' => '00003', "data" => $this->request->jpost("SSID")));
            }

        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_getUserInfo()
    {
        if ($this->request->jpost('SSID') && $this->request->jpost('userID')) {
            $ssID = $this->request->jpost('SSID');
            $userID = $this->request->jpost('userID');
            $ownerID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($ownerID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                if ($userID == 0) {
                    $accountInfo = $this->account_model->getAccountInfo($ownerID);
                    $accountInfo['current_user'] = 1;
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $accountInfo->as_array()));
                } else {
                    $accountInfo = $this->account_model->getAccountInfo($userID);
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $accountInfo->as_array()));
                }
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }
    
    public function action_getNearestUsers() {
        if ($this->request->jpost('distance') && $this->request->jpost('SSID') && $this->request->jpost('limit')) {
            $ssID = $this->request->jpost('SSID');
            $userID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($userID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                $limit = $this->request->jpost('limit');
                $distance = $this->request->jpost('distance');
                $nearestUsers = $this->account_model->getNearestUsers($userID, $distance, $limit);
                $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $nearestUsers));
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_getFacebook()
    {
        if ($this->request->jpost('user_id') && $this->request->jpost('access_token')) {
            $user_id = $this->request->jpost('user_id');
            $access_token = $this->request->jpost('access_token');
            $facebook_data = Facebook::instance()->login($user_id, $access_token);
            if ($facebook_data['code'] == 1) {
                $this->response->body(array('msg' => $facebook_data['data'], 'code' => '00003'));
            } else {
                $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $facebook_data['data']));
            }

        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }
    
    public function action_getTwitter() {
        if ($this->request->jpost('user_id') && $this->request->jpost('access_token') && $this->request->jpost('access_secret_token')) {
            $user_id = $this->request->jpost('user_id');
            $access_token = $this->request->jpost('access_token');
            $access_secret_token = $this->request->jpost('access_secret_token');
            $twitter = Twitter::instance()
                ->set_access_token($access_token)
                ->set_access_secret_token($access_secret_token);
            try {
                $account_id = $twitter->login($user_id);
            } catch (Kohana_Exception $e) {
                $error_message = $e->getMessage();
                return $this->response->body(array('msg' => $error_message, 'code' => '00003'));
            }
            if ($account_id) {
                $account = ORM::factory("Account", $account_id);
                if ($account->loaded()) {
                    $profile = ORM::factory("Profile")->where("account_id", "=", $account->userID)->find();
                    $session = ORM::factory('Session')->where("user_id","=",$account_id);
                    $data = $account->as_array();
                    $data["profile"] = $profile->as_array();
                    $data["profile"]['SSID'] = $session->SSID;
                    return $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $data));
                } else {
                    $this->response->body(array('msg' => "Login failed", 'code' => '00003'));
                }
            } else {
                $this->response->body(array('msg' => "Login failed", 'code' => '00003'));
            }

        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }


    public function action_getVk()
    {
        if ($this->request->jpost('user_id') && $this->request->jpost('access_token')) {
            $user_id = $this->request->jpost('user_id');
            $access_token = $this->request->jpost('access_token');
            $vk = VK::instance();
            try {
                $account_id = $vk->login($user_id, $access_token);
            } catch (Kohana_Exception $e) {
                $error_message = $e->getMessage();
                return $this->response->body(array('msg' => $error_message, 'code' => '00003'));
            }
            if ($account_id) {
                $account = ORM::factory("Account", $account_id);
                if ($account->loaded()) {
                    $profile = ORM::factory("Profile")->where("account_id", "=", $account->userID)->find();
                    $session = ORM::factory('Session')->where("user_id","=",$account_id);
                    $data = $account->as_array();
                    $data["profile"] = $profile->as_array();
                    $data["profile"]['SSID'] = $session->SSID;
                    return $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $data));
                } else {
                    $this->response->body(array('msg' => "Login failed", 'code' => '00003'));
                }
            } else {
                $this->response->body(array('msg' => "Login failed", 'code' => '00003'));
            }

        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }
    
    public function action_getGoogle() {
        if ($this->request->jpost('user_id')) {
            $user_id = $this->request->jpost('user_id');
            $google_data = Google::instance()->login($user_id);
            if ($google_data['code'] == 1) {
                $this->response->body(array('msg' => $google_data['data'], 'code' => '00003'));
            } else {
                $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $google_data['data']));
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

}

