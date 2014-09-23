<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Session extends ORM
{

    protected $_table_name = 'sessions';
    protected $_primary_key = 'id';

    protected $_has_one = array(
        'account' => array(
            'model' => 'Account',
            'foreign_key' => 'id',
        ),
    );

    public function saveSession($user_id, $session_id)
    {
        $this->set('userID', $user_id)->set('SSID', $session_id)->create();
    }

    public function getSessionByUserId($user_id)
    {
        $session = $this->where('userID', '=', $user_id)->find();
        return $session;
    }

    public function getSessionById($SSID)
    {
        $session = $this->where('SSID', '=', $SSID)->find();
        return $session;
    }   
    public function getUserIdBySession($ssid) {
        $session = $this->select('userID')->where('SSID','=',$ssid)->find();
        $userID = $session->loaded() ? $session->userID : 0;
        return $userID;
    }

}

?>
