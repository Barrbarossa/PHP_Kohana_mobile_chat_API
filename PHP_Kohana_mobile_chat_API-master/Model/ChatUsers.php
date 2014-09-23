<?php

defined('SYSPATH') or die('No direct script access.');

class Model_ChatUsers extends ORM
{

    protected $_table_name = 'chat_users';
    protected $_primary_key = 'id';

    protected $_belongs_to = array(
        'chat' => array(
            'model' => 'Chat',
            'foreign_key' => 'chatID',
        ),
         'user' => array(
            'model' => 'Account',
            'foreign_key' => 'userID',
        ),
    );
    
    public function isMemberChat($userID, $chatID) {
        $chatUser = ORM::factory("ChatUsers")->where('chatID','=',$chatID)->where('userID','=',$userID)->where('status','=',1)->find();
        if ($chatUser->loaded()) {
            return true;
        }
        return false;
    }

    public function getChatMembers($chatID) {
        $chatMembers = $this->with('user')->where('chatID','=',$chatID)->where('status','=',1)->find_all();
        $chatMembersData = array();
        $i = 0;
        foreach ($chatMembers as $chatMember) {
            $chatMembersData[$i]['userID'] = $chatMember->userID;
            $chatMembersData[$i]['login'] = $chatMember->user->login;
            $chatMembersData[$i]['username'] = $chatMember->user->username;
            $chatMembersData[$i]['latitude'] = $chatMember->user->latitude;
            $chatMembersData[$i]['longitude'] = $chatMember->user->longitude;
            $i++;
        }
        return $chatMembersData;
    }
    
    public function joinInChat($chatID, $users) {
        foreach ($users as $user) {
            if ($this->isMemberChat($user, $chatID) === FALSE) {
                $this->set('chatID', $chatID)->set('userID', $user)->set('status', 1)->create();
                $this->clear();
            } else {
                $this->clear();
            }
        }
    }
    
    public function getChatForUser($userID) {
        $chats = $this->where('userID','=',$userID)
                ->where('status','=',1)
                ->find_all();
        $chatsData = array();
        $i = 0;
        foreach ($chats as $chat) {
            $chatsData[$i]['chatID'] = $chat->chatID;
            $chatsData[$i]['status'] = $chat->status;
            $i++;
        }
        return $chatsData;
    }
    
    public function removeUser($chatID, $userID) {
        $chatUser = $this->where('chatID','=',$chatID)->where('userID','=',$userID)->find();
        if ($chatUser->loaded()) {
            $this->set('status',0)->update();
        }
    }
    
    public function getMemeberCount($chatID) {
        $memberCount = $this->where('chatID','=',$chatID)->where('status','=',1)->count_all();
        return $memberCount;
    }
    
    public function deleteMembers($chatID) {
        $chatUsers = $this->where('chatID','=',$chatID)->find_all();
        foreach ($chatUsers as $chatUser) {
            $chatUser->delete();
        }
    }

}

?>
