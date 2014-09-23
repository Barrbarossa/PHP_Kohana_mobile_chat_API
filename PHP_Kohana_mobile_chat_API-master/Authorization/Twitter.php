<?php

defined('SYSPATH') or die('No direct script access.');

class Authorization_Twitter extends Singleton implements ISocialAuthorization
{

    //Response URL & Method Creation
    public $api_url = "https://api.twitter.com/1.1/";
    public $method;
    public $request_url;
    public $request_options;

    //Access Keys and Signature
    private $oauth_access_token = "497194949-yTZlUGIkkaiZ3O8OCiCAzAPFDmnsMoRjFvzVMuMw";
    private $oauth_access_token_secret = "7hzUOKoyqTiPdsCVZXxRQYULCMhrUZIROcSk5MHtAI";
    private $consumer_key = "C0Y7JUXDt6EJmr4UgqdX1Q";
    private $consumer_secret = "pAVg43eHbwoMQZBCan8w3yrRJQOwIQFgPhlLuD4";

    //Header Params
    private $oAuth_params;

    public $post_fields;

    public function get($data)
    {

    }

    public function login($user_id, $access_token = "")
    {
        $user_profile = ORM::factory("Profile")
            ->where("twitter_id", "=", $user_id)
            ->find();
        if (!$user_profile->loaded()) {
            $this->request('GET', 'users/show.json');
            $this->setQuery(array('user_id' => $user_id));
            $response = $this->sendRequest();
            if (isset($response->errors)) {
                $msg = "Login failed with errors: '";
                foreach($response->errors as $error){
                    $msg .= "$error->message, Code: $error->code' '";
                }
                throw new Kohana_Exception($msg);
            }
            $name = explode(" ", $response->name);
            $data = array("profile" => array(
                "gender" => '',
                "firstname" => Arr::get($name, 0),
                "lastname" => Arr::get($name, 1),
                "twitter_id" => $response->id
            ),
                'login' => '',
                'password' => '',
                'username' => '',
                'latitude' => '',
                'longitude' => ''
            );

            $account = ORM::factory("Account")->_create($data);
            if ($account)
                return $account;
            else
                return false;
        } else {
            return $user_profile->account->userID;
        }
    }

//Base configuration
//
    public function request($method, $request)
    {

        $this->method = strtoupper($method);
        $this->request_url = $this->api_url . $request;

        //Build default oAuth Param array
        $this->oAuth_params = array(
            'oauth_consumer_key' => $this->consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $this->oauth_access_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );
    }

    public function setQuery($options)
    {

        $concatenated = "";

        switch ($this->method) {
            case 'POST':
                $this->post_fields = $options;
                break;
            case 'GET':
                //Merge options to default oAuth config.
                $this->oAuth_params = array_merge($this->oAuth_params, $options);
                //Concatenate array to string
                foreach ($options as $key => $value) {
                    $concatenated .= "$key=" . rawurlencode($value) . "&";
                }

                $this->request_options = rtrim($concatenated, "&");
                break;
        }
    }


//Header and Siganture Creation
//
    private function buildBaseURL($response_url, $oauth_params)
    {

        ksort($oauth_params); // important!
        $oauth_keyValues = array();

        foreach ($oauth_params as $key => $value) {
            $oauth_keyValues[] = "$key=" . rawurlencode($value);
        }

        return $this->method . "&" . rawurlencode($response_url) . "&" . rawurlencode(implode("&", $oauth_keyValues));
    }


    private function buildAuthHeader($oauth_params)
    {

        $auth_string = 'Authorization: OAuth ';
        $oauth_keyValues = array();

        foreach ($oauth_params as $key => $value) {
            $oauth_keyValues[] = "$key=\"" . rawurlencode($value) . "\"";
        }

        $auth_string .= implode(', ', $oauth_keyValues);

        return $auth_string;
    }

    private function buildAuthSignature()
    {

        $base_info = $this->buildBaseURL($this->request_url, $this->oAuth_params);
        $composite_key = rawurlencode($this->consumer_secret) . "&" . rawurlencode($this->oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $this->oAuth_params['oauth_signature'] = $oauth_signature;
    }


//Connect & Send Request
//
    public function sendRequest()
    {


        //Build Header and Append Options
        $this->buildAuthSignature();
        $header = array($this->buildAuthHeader($this->oAuth_params), 'Expect:');
        isset($this->request_options) && $this->method == "GET" ? $request_url = $this->request_url . "?" . $this->request_options : $request_url = $this->request_url;

        //Initialize cURL
        $curl_request = curl_init();

        //curl_setopt($curl_request, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($curl_request, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($curl_request, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl_request, CURLOPT_HEADER, false);
        curl_setopt($curl_request, CURLINFO_HEADER_OUT, false);
        curl_setopt($curl_request, CURLOPT_URL, $request_url);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl_request, CURLOPT_CAINFO, "certs/ca-bundle.crt");


        switch ($this->method) {
            case 'POST':
                curl_setopt($curl_request, CURLOPT_POST, true);
                if (!empty($this->post_fields)) {
                    curl_setopt($curl_request, CURLOPT_POSTFIELDS, $this->post_fields);
                }
                break;
        }


        $response = curl_exec($curl_request);
        /* print_r(curl_getinfo($curl_request)); */
        curl_close($curl_request);

        //Decode JSON and return data
        $twitter_data = json_decode($response);

        return $twitter_data;
    }

    public function set_access_token($token = "")
    {
        $this->oauth_access_token = $token;
        return $this;
    }

    public function set_access_secret_token($token = "")
    {
        $this->oauth_access_secret_token = $token;
        return $this;
    }

}