<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Message extends ORM {

    protected $_table_name = 'messages';
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
    protected $_has_many = array(
        'user_messages' => array(
            'model' => 'UserMessages',
            'foreign_key' => 'messageID',
        ),
        'attachments' => array(
            'model' => 'Attachment',
            'foreign_key' => 'messageID'
        )
    );

    public function saveMessage($chatID, $text, $userID) {
        $chatUsers_model = ORM::factory('ChatUsers');
        $chatMembers = $chatUsers_model->getChatMembers($chatID);
        foreach ($chatMembers as $key => $chatMember) {
            if ($chatMember['userID'] == $userID) {
                 unset($chatMembers[$key]);
                 break;
            }
        }
        $date = time();
        $this->set('chatID', $chatID)
                ->set('text', $text)
                ->set('userID', $userID)
                ->set('date', $date)
                ->create();
        $messageID = $this->id;
        foreach ($chatMembers as $chatMember) {
            $this->user_messages->set('userID',$chatMember['userID'])->set('messageID',$messageID)->set('isRead',0)->create();
        }
        $message['chatID'] = $chatID;
        $message['msgID'] = $messageID;
        $message['text'] = $text;
        $message['userID'] = $userID;
        $message['date'] = $date;
        $message['isRead'] = 0;

        return $message;
    }
    
    public function getAllMessages($chatID) {
        $messages = $this->with('user')->where('chatID','=',$chatID)->find_all();
        $messages_text = array();
        $i = 0;
        foreach ($messages as $message) {
            $messages_text[$i]['userID'] = $message->userID;
            $messages_text[$i]['username'] = $message->user->username;
            $messages_text[$i]['text'] = $message->text;
            $messages_text[$i]['date'] = $message->date;
            $messages_text[$i]['attachment'] = '';
            $i++;
        }
        return $messages_text;
    }
    
    public function deleteMessagesForChat($chatID) {
        $messages = $this->where('chatID','=',$chatID)->find_all();
        foreach ($messages as $message) {
            $message->delete();
        }
    }

}

?>
