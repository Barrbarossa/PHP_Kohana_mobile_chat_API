<?php
defined('SYSPATH') or die('No direct script access.');

class Authorization_VK extends Singleton implements ISocialAuthorization
{

    protected $api_url;

    function __construct()
    {
        $this->api_url = Kohana::$config->load("authorization.vk.api_base_url");
    }

    public function login($user_id, $access_token)
    {

        $user_profile = ORM::factory("Profile")
            ->where("vk_id", "=", $user_id)
            ->find();
        if (!$user_profile->loaded()) {
            $request = Request::factory($this->api_url . "users.get");
            $request->method(Request::GET);
            $request->query(array(
                "uids" => $user_id,
                "access_token" => $access_token,
                "fields" => "sex"
            ));


            // преобразовываем ответ в читаемый вид
            $response = $request->execute();
            $response = (array) $response->body();
            $response = json_decode($response[0], TRUE);
            if(isset($response["error"])){
                throw new Kohana_Exception("Login failed with error: '" . $response["error"]["error_msg"] . "', and with code " . $response["error"]["error_code"]);
            }
            $response = $response["response"][0];
            //преобразования закончены


            $data = array("profile" => array(
                "gender" => $response["sex"],
                "firstname" => $response["first_name"],
                "lastname" => $response["last_name"],
                "vk_id" => $response["uid"]
            ),
            'login' => '',
            'password' => '',
            'username' => '',
            'latitude' => '',
            'longitude' => ''
            );

            $account = ORM::factory("Account")->_create($data);
            if($account)
                return $account;
            else
                return false;
        } else {
            return $user_profile->account->userID;
        }
    }

    public function get($data)
    {
        // TODO: Implement get() method.
    }
}