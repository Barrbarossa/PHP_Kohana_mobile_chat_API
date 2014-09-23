<?php

defined('SYSPATH') or die('No direct script access.');

interface ISocialAuthorization {

    public function login($user_id, $access_token);

    public function get($data);

}