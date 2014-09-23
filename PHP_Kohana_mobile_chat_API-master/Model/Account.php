<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Account extends ORM
{

    protected $_table_name = 'accounts';
    protected $_primary_key = 'userID';
    private $crypt_algor = '$2a$07$adgj52hnbn6Fadvx9hThfV';
    private $salt = 'qPmV31Fanvtlhanv16h6n1hbvf$';

    protected $_has_one = array(
        'session' => array(
            'model' => 'Session',
            'foreign_key' => 'userID',
        ),

        'profile' => array(
            'model' => 'Profile',
            'foreign_key' => 'account_id',
        ),
    );
    protected $_has_many = array(
        'chats' => array(
            'model' => 'Chat',
            'foreign_key' => 'ownerID',
        ),
    );

    public function cryptPassword($password)
    {
        $password = crypt($password, $this->crypt_algor . $this->salt);
        return substr($password, 30);
    }

    public function existsAccount($login)
    {
        $account = $this->where('login', '=', $login)->count_all();
        if ($account < 1)
            return false;
        return true;
    }

    public function getAccountByLogin($login)
    {
        $account = $this->where('login', '=', $login)->find();
        return $account;
    }

    public function equalPassword($input_pass, $acc_pass)
    {
        $input_pass = substr(crypt($input_pass, $this->crypt_algor . $this->salt), 30);
        if ($input_pass !== $acc_pass) {
            return false;
        }
        return true;
    }

    public function _create($data)
    {
        $account = ORM::factory('Account');
        if (isset($data["profile"])) {
            $data_profile = $data["profile"];
            unset($data["profile"]);
        } else {
            $data_profile = NULL;
        }
        foreach ($data as $column => $value) {
            $account->set($column, $value);
        }
        if ($account->create()) {
            $profile = ORM::factory("Profile");
            if (!is_null($data_profile)) {
                foreach ($data_profile as $key => $value) {
                    $profile->$key = $value;
                }
            }
            $profile->account_id = $account->userID;
            $profile->save();
            return $account->userID;
        }
        return false;
    }

    public function getAll()
    {
        return $this->find_all();
    }


    public function getAccountInfo($userID)
    {
        $account = $this->select(array('userID', 'login', 'username', 'latitude', 'longitude'))->where('userID', '=', $userID)->with('profile')->find();
        return $account;
    }

    public function getNearestUsers($userID, $distance, $limit)
    {
        $userAccount = $this->where('userID', '=', $userID)->find();
        $userLatitude = floatval($userAccount->latitude);
        $userLongitude = floatval($userAccount->longitude);
        $this->clear();
        $accounts = $this->where('userID', '<>', $userID)->find_all();

        $distances = array();
        $nearestUsers = array();
        $i = 0;
        $distance = floatval($distance);

        foreach ($accounts as $account) {
            $latirude = floatval($account->latitude);
            $longitude = floatval($account->longitude);
            $theta = $userLongitude - $longitude;
            $dist = sin(deg2rad($userLatitude)) * sin(deg2rad($latirude)) + cos(deg2rad($userLatitude)) * cos(deg2rad($latirude)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $kilom = $dist * 60 * 1.1515 * 1.609344;
            if ($kilom < $distance) {
                $distances[$i]['distance'] = $kilom;
                $nearestUsers[$i]['username'] = $account->username;
                $nearestUsers[$i]['latitude'] = $account->latitude;
                $nearestUsers[$i]['longitude'] = $account->longitude;
                $nearestUsers[$i]['userID'] = $account->userID;
                $i++;
            }
        }
        $distance_array = array();
        foreach ($distances as $key => $dist) {
            $distance_array[$key] = $dist['distance'];
        }
        array_multisort($distance_array, SORT_NUMERIC, $nearestUsers);
        return array_slice($nearestUsers, 0, $limit);
    }

    public function getUsernameById($userID)
    {
        $account = $this->select('username')->where('userID', '=', $userID)->find();
        return $account->username;
    }

}
