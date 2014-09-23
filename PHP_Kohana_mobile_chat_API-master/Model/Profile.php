<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Profile extends ORM {
    
    protected $_table_name = 'profile';
    protected $_primary_key = 'id';
    
    protected $_belongs_to = array(
        'account' => array(
            'model' => 'Account',
            'foreign_key' => 'account_id',
        )
    );
    
    public function getAccountByFacebookId($profile_id) {
        $profile = $this->with('account')->where('facebook_id','=',$profile_id)->find();
        if ($profile->loaded()) {
            return $profile->account;
        } 
        return false;
    }
    
    public function getAccountByGoogleId($profile_id) {
        $profile = $this->with('account')->where('google_id','=',$profile_id)->find();
        if ($profile->loaded()) {
            return $profile->account;
        } 
        return false;
    }
}