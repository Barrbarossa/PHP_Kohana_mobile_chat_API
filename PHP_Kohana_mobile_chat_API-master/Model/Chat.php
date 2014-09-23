<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Chat extends ORM
{

    protected $_table_name = 'chats';
    protected $_primary_key = 'id';

    protected $_belongs_to = array(
        'account' => array(
            'model' => 'Account',
            'foreign_key' => 'ownerID',
        ),
    );

    
    public function getChatById($chatID) {
        $chat = ORM::factory("Chat")->where('id','=',$chatID)->find();
        return $chat;
    }
    
    public function createChat($ownerID,$chatName = '') {
        $this->set('ownerID',$ownerID)
                ->set('chatName',$chatName)
                ->create();
        return $this->id;
    }
    
    public function isOwnerChat($ownerID,$chatID) {
        $chat = $this->where('id','=',$chatID)
                ->where('ownerID','=',$ownerID)
                ->find();
        if ($chat->loaded()) {
            return true;
        } else {
            return flase;
        }
    }
    
    public function deleteChat($chatID) {
        $this->where('id','=',$chatID)->find();
        $this->delete();
    }

    public function getChatIDForTwoUsers($owner, $userid) {
        $owner_model = ORM::factory("Account", $owner);
        $chats = $owner_model->chats->find_all()->as_array();
        $chatIDs = array();
        foreach ($chats as $chat) {
            $chatIDs[] = $chat->id;
        }
        if (empty($chatIDs)) {
            $chatIDs[] = 0;
        }

        $chatUsers = ORM::factory("ChatUsers")
            ->where("chatID", "IN", $chatIDs)
            ->and_where("userID", "=", $userid)
            ->find_all()
            ->as_array();

        $result = "";

        foreach ($chatUsers as $chat) {
            if(ORM::factory("ChatUsers")->where("chatID", '=', $chat->chatID)->count_all() == 2){
                return $chat->chatID;
            }
        }
        return null;
    }

}

?>
