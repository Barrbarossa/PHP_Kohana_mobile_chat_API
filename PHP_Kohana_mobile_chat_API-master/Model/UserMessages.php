<?php

defined('SYSPATH') or die('No direct script access.');

class Model_UserMessages extends ORM
{

    protected $_table_name = 'user_messages';
    protected $_primary_key = 'id';

    protected $_belongs_to = array(
        'message' => array(
            'model' => 'Message',
            'foreign_key' => 'messageID',
        ),
        'user' => array(
            'model' => 'Account',
            'foreign_key' => 'userID',
        ),
    );

    public function markAsRead($chatID, $userID, $messagesID)
    {
        $messages = ORM::factory("UserMessages")
            ->where("messageID", "IN", $messagesID)
            ->and_where("userID", "=", $userID)
            ->find_all();

        if ($messages->count() > 0) {
            foreach ($messages as $message) {
                $message->isRead = 1;
                $message->save();
            }
            return true;
        } else {
            return false;
        }
    }


    public function getUnreadMessagesData($chatID, $userID)
    {
        $unReadMessges = $this
            ->with('message')
            ->where('usermessages.userID', '=', $userID)
            ->where('usermessages.isRead', '=', 0)
            ->where('message.userID', '<>', $userID)
            ->where('message.chatID', '=', $chatID)
            ->find_all();
        $messages = array();
        $i = 0;
        foreach ($unReadMessges as $unReadMessge) {
            $messages[$i]['chatID'] = $unReadMessge->message->chatID;
            $messages[$i]['msgID'] = $unReadMessge->messageID;
            $messages[$i]['text'] = $unReadMessge->message->text;
            $messages[$i]['userID'] = $unReadMessge->userID;
            $messages[$i]['date'] = $unReadMessge->message->date;
            $messages[$i]['isRead'] = 0;
            $i++;
        }
        return $messages;
    }

    public function getUnreadMessages($userID, $chatID)
    {
        $unReadMessages = $this->with('message')->with('user')
            ->where('usermessages.userID', '=', $userID)
            ->where('message.chatID', '=', $chatID)
            ->order_by('message.id', 'DESC')
            ->limit(10)
            ->find_all();
        $messagesText = array();
        $i = 0;
        foreach ($unReadMessages as $unReadMessage) {
            $messagesText[$i]['userID'] = $unReadMessage->message->userID;
            if ($unReadMessage->message->userID == 0) {
                $messagesText[$i]['username'] = 'Admin';
            } else {
                $messagesText[$i]['username'] = $unReadMessage->user->username;
            }
            $messagesText[$i]['msgID'] = $unReadMessage->message->id;
            $messagesText[$i]['text'] = $unReadMessage->message->text;
            $messagesText[$i]['date'] = $unReadMessage->message->date;
            $messagesText[$i]['attachment'] = '';
            $i++;
        }
        return $messagesText;
    }
    
    public function getUnreadMsgCount($chatID,$userID){
        $unReadMessages = $this->with('message')->where('message.chatID','=',$chatID)
                ->where('usermessages.userID','=',$userID)
                ->where('usermessages.isRead','=',0)
                ->find_all();
        return count($unReadMessages);
    }
    
    public function getAllMessages($chatID, $userID, $limit = 10, $lastMessageID = 0) {
        if ($lastMessageID == 0) {
            $this->with('message')->where('message.chatID', '=', $chatID)->where('usermessages.userID','=',$userID);
        } else {
            $this->with('message')->where('message.chatID', '=', $chatID)->where('usermessages.userID','=',$userID)->where('message.id','<=',$lastMessageID);
        }
        $messages = $this->with('user')
                ->order_by('message.id', 'DESC')
                ->limit($limit)
                ->select(array('usermessages.messageID','message.text','message.date','usermessages.isRead','message.userID','user.username',))
                ->find_all();
        
        $message_text = array();
        $i = 0;
        foreach ($messages as $message) {
            $message_text[$i]['text'] = $message->message->text;
            $message_text[$i]['isRead'] = $message->isRead;
            $message_text[$i]['userID'] = $message->message->userID;
            $message_text[$i]['username'] = $message->user->username;
            $message_text[$i]['msgID'] = $message->messageID;
            $message_text[$i]['date'] = $message->message->date;
            $i++;
        }
        return $message_text;
    }
    
    public function getMessagesForChatMoreId($min_id, $userID, $chatID) {
        $messages = $this->with('message')->with('user')
                ->where('message.chatID', '=', $chatID)
                ->where('message.id', '>=', $min_id)
                ->where('usermessages.userID', '=', $userID)
                ->order_by('message.id', 'DESC')
                ->find_all();
        $message_text = array();
        $i = 0;
        foreach ($messages as $message) {
            $message_text[$i]['text'] = $message->message->text;
            $message_text[$i]['isRead'] = $message->isRead;
            $message_text[$i]['userID'] = $message->message->userID;
            $message_text[$i]['username'] = $message->user->username;
            $message_text[$i]['messageID'] = $message->messageID;
            $message_text[$i]['date'] = $message->message->date;
            $i++;
        }
        return $message_text;
    }

}