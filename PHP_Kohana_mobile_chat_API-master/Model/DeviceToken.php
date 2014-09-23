<?php

defined('SYSPATH') or die('No direct script access.');

class Model_DeviceToken extends ORM
{

    protected $_table_name = 'device_tokens';
    protected $_primary_key = 'id';

    public function is_Exist($platformID, $debugFlag, $token, $user_id)
    {
        $device_token = $this->where('token', '=', $token)
            ->where('platformId', '=', $platformID)
            ->where('debugFlag', '=', $debugFlag)
            ->where('userID', '=', $user_id)
            ->find();
        if ($device_token->loaded())
            return TRUE;
        else return FALSE;
    }

    public function saveToken($platformID, $debugFlag, $token, $user_id)
    {
        $this->set('platformId', $platformID)
            ->set('debugFlag', $debugFlag)
            ->set('token', $token)
            ->set('userID', $user_id)
            ->create();
    }

}

?>
