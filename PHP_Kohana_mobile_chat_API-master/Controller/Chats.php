<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Chats extends Controller
{

    private $account_model;
    private $session_model;
    private $chat_model;
    private $chatUsers_model;
    private $message_model;
    private $userMessages_model;
    private $attachment_model;

    public function before()
    {
        $this->account_model = ORM::factory('Account');
        $this->session_model = ORM::factory('Session');
        $this->chat_model = ORM::factory('Chat');
        $this->chatUsers_model = ORM::factory('ChatUsers');
        $this->message_model = ORM::factory('Message');
        $this->userMessages_model = ORM::factory('UserMessages');
        $this->attachment_model = ORM::factory('Attachment');
        parent::before();
    }

    public function action_postMessage()
    {
        if ($this->request->jpost('chatID') && $this->request->jpost('SSID') && $this->request->jpost('text')) {
            $ssID = $this->request->jpost('SSID');
            $chatID = $this->request->jpost('chatID');
            $text = $this->request->jpost('text');
            $userID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($userID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                $chat = $this->chat_model->getChatById($chatID);
                if (!$chat->loaded()) {
                    $this->response->body(array('msg' => 'Chat not found', 'code' => '00008'));
                } else {
                    if ($this->chatUsers_model->isMemberChat($userID, $chatID)) {
                        $message = array();
                        $lastMessage = $this->message_model->saveMessage($chatID, $text, $userID);
                        array_push($message, $lastMessage);
                        $unreadMessages = $this->userMessages_model->getUnreadMessagesData($chatID, $userID);
                        $messages = array_merge($message, $unreadMessages);

                        $attachments = $this->request->jpost('attachments');
                        $attachments_data = array();
                        $i = 0;
                        foreach ($attachments as $attachment) {
                            $file_name = $attachment->name;
                            $file_extension = $attachment->extension;
                            $file_data = $attachment->data;
                            $attachment_id = $this->attachment_model->saveAttachment($file_name,$file_extension,$lastMessage['msgID']);
                            $this->attachment_model->clear();
                            $hash_name = md5($attachment_id);
                            $file_path = DOCROOT.'attachments/'.$hash_name.'.'.$file_extension;
                            file_put_contents($file_path, base64_decode($file_data));
                            $attachments_data[$i]['name'] = $file_name;
                            $attachments_data[$i]['expansion'] = $file_extension;
                            $attachments_data[$i]['hash_name'] = $hash_name;
                            $attachments_data[$i]['link'] = URL::base(TRUE, TRUE).'attachments/'.$hash_name.'.'.$file_expansion;
                            $i++;
                        }
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'messages' => $messages,'attachments' => $attachments_data));
                    } else {
                        $this->response->body(array('msg' => 'This user is not a member in chat', 'code' => '00009'));
                    }
                }
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_createChat()
    {
        if ($this->request->jpost('userID') && $this->request->jpost('SSID')) {
            $SSID = $this->request->jpost("SSID");
            $owner = $this->session_model->getUserIdBySession($SSID);
            if ($owner != 0) {
                $chatName = $this->request->jpost("chatName", false) ? $this->request->jpost("chatName") : "";
                $user = $this->request->jpost("userID");
                if ($owner == $user) {
                    return $this->response->body(array('msg' => 'Error', 'code' => '00011'));
                }
                $chat = $this->chat_model->getChatIDForTwoUsers($owner, $user);
                if (!is_null($chat)) {
                    $chatForTwoUsers = $this->chat_model->getChatById($chat);
                    $userName = ORM::factory("Account", $owner)->username;
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => array(
                        "chatID" => $chatForTwoUsers->id,
                        "chatName" => $chatForTwoUsers->chatName,
                        "owner" => $userName,
                        "ownerID" => $owner
                    )));
                } else {
                    $chatForTwoUsers = $this->chat_model->createChat($owner, $chatName);
                    $this->chatUsers_model->joinInChat($chatForTwoUsers, array($owner, $user));
                    $chat = $this->chat_model->getChatById($chatForTwoUsers);
                    $userName = ORM::factory("Account", $owner)->username;
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => array(
                        "chatID" => $chat->id,
                        "chatName" => $chat->chatName,
                        "owner" => $userName,
                        "ownerID" => $owner
                    )));
                }
            } else {
                return $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            }
        } else {
            return $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_openChat()
    {
        if ($this->request->jpost('chatID') && $this->request->jpost('SSID')) {
            $ssID = $this->request->jpost('SSID');
            $chatID = $this->request->jpost('chatID');
            $userID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($userID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                if ($this->chatUsers_model->isMemberChat($userID, $chatID)) {
                    $this->chatUsers_model->clear();
                    $chatMembers = $this->chatUsers_model->getChatMembers($chatID);
                    $unreadMessages = $this->userMessages_model->getUnreadMessages($chatID, $userID);
                    $chatInfo = $this->chat_model->getChatById($chatID);
                    $userName = $this->account_model->getUsernameById($chatInfo->ownerID);
                    //$this->api_result = array('msg' => 'success', 'code' => '10001', 'data' => array('members' => $chatMembers, 'Messages' => $unreadMessages, 'ChatInfo' => array('chatID' => $chatInfo['id'], 'chatName' => $chatInfo['chatName'], 'owner' => $userName['Accounts']['username'], 'ownerID' => $chatInfo['ownerID'])));
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => array('members' => $chatMembers, 'Messages' => $unreadMessages, 'ChatInfo' => array('chatID' => $chatInfo->id, 'chatName' => $chatInfo->chatName, 'owner' => $userName, 'ownerID' => $chatInfo->ownerID))));
                } else {
                    $this->response->body(array('msg' => 'This user is not a member in chat', 'code' => '00009'));
                }
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_inviteUsersToChat()
    {
        if ($this->request->jpost('chatID') === "0" && $this->request->jpost('SSID') && $this->request->jpost('users')) {
            $ssID = $this->request->jpost('SSID');
            $chatID = $this->request->jpost('chatID');
            $users = $this->request->jpost('users');
            $userID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($userID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                if ($chatID == 0) {
                    $chatID = $this->chat_model->createChat($userID);
                    $this->chatUsers_model->joinInChat($chatID, $users);
                    $this->response->body(array('msg' => 'success', 'code' => '10001'));
                } else {
                    $chat = $this->chat_model->getChatById($chatID);
                    if (sizeof($chat) < 1) {
                        $this->response->body(array('msg' => 'Chat not found', 'code' => '00008'));
                    } else {
                        $this->chatUsers_model->joinInChat($chatID, $users);
                        $messages = $this->message_model->getAllMessages($chatID);
                        $this->response->body(array('msg' => 'success', 'code' => '10001', 'messages' => $messages));
                    }
                }
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }
    
    public function action_chatList() {
        if ($this->request->jpost('SSID')) {
            $ssID = $this->request->jpost('SSID');
            $userID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($userID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                $chats = $this->chatUsers_model->getChatForUser($userID);
                $chatData = array();
                $i = 0;
                foreach ($chats as $chat) {
                    $members = $this->chatUsers_model->getChatMembers($chat['chatID']);
                    $chatData[$i]['chatID'] = $chat['chatID'];
                    $chatData[$i]['members'] = $members;
                    $chatData[$i]['unread'] = $this->userMessages_model->getUnreadMsgCount($chat['chatID'], $userID);
                    $i++;
                }
                $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $chatData));
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }
    
    public function action_chatMessages() {
        if ($this->request->jpost('chatID') && $this->request->jpost('SSID') && !$this->request->jpost('MessagesAfter')) {
            $chatID = $this->request->jpost('chatID');
            $ssID = $this->request->jpost('SSID');
            $userID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($userID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                if ($this->request->jpost('limit')) {
                    $limit = $this->request->jpost('limit');
                } else {
                    $limit = 10;
                }
                if ($this->request->jpost('lastMessageID')) {
                    $lastMessageID = $this->request->jpost('lastMessageID');
                } else {
                    $lastMessageID = 0;
                }
                if ($this->chatUsers_model->isMemberChat($userID, $chatID)) {
                    $messages = $this->userMessages_model->getAllMessages($chatID, $userID, $limit, $lastMessageID);
                    $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $messages));
                } else {
                    $this->response->body(array('msg' => 'This user is not a member in chat', 'code' => '00009'));
                }
            }
        } else if ($this->request->jpost('MessagesAfter') && $this->request->jpost('MessagesAfter') == 0 && $this->request->jpost('messageID') && $this->request->jpost('chatID') && $this->request->jpost('SSID')) {
            $chatID = $this->request->jpost('chatID');
            $ssID = $this->request->jpost('SSID');
            $userID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($userID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                $messageID = $this->request->jpost('messageID');
                $messages = $this->userMessages_model->getMessagesForChatMoreId($messageID, $userID, $chatID);
                $this->response->body(array('msg' => 'success', 'code' => '10001', 'data' => $messages));
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }
    
    public function action_removeUserFromChat() {
        if ($this->request->jpost('chatID') && $this->request->jpost('SSID') && $this->request->jpost('userID')) {
            $chatID = $this->request->jpost('chatID');
            $memberID = $this->request->jpost('userID');
            $ssID = $this->request->jpost('SSID');
            $ownerID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($ownerID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                if ($this->chatUsers_model->isMemberChat($memberID, $chatID)) {
                    if ($this->chat_model->isOwnerChat($ownerID, $chatID)) {
                        $this->chatUsers_model->clear();
                        $this->chatUsers_model->removeUser($chatID, $memberID);
                        $this->chatUsers_model->clear();
                        if ($this->chatUsers_model->getMemeberCount($chatID) < 2) {
                            $this->chat_model->deleteChat($chatID);
                            $this->chatUsers_model->clear();
                            $this->chatUsers_model->deleteMembers($chatID);
                            $this->message_model->deleteMessagesForChat($chatID);
                        }
                        $message_text = 'User ' . $memberID . ' was cicked by ' . $ownerID;
                        $this->message_model->saveMessage($chatID, $message_text, 0);
                        $this->response->body(array('msg' => 'success', 'code' => '10001'));
                    } else {
                        $this->response->body(array('msg' => 'You are not the owner of the chat', 'code' => '00010'));
                    }
                } else {
                    $this->response->body(array('msg' => 'This user is not a member in chat', 'code' => '00009'));
                }
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }
    
    public function action_exitChat() {
        if ($this->request->jpost('chatID') && $this->request->jpost('SSID')) {
            $chatID = $this->request->jpost('chatID');
            $ssID = $this->request->jpost('SSID');
            $userID = $this->session_model->getUserIdBySession($ssID);
            if (sizeof($userID) < 1) {
                $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));
            } else {
                if ($this->chatUsers_model->isMemberChat($userID, $chatID)) {
                    $this->chatUsers_model->clear();
                    $this->chatUsers_model->removeUser($chatID, $userID);
                    $this->chatUsers_model->clear();
                    if ($this->chatUsers_model->getMemeberCount($chatID) < 2) {
                        $this->chat_model->deleteChat($chatID);
                        $this->chatUsers_model->clear();
                        $this->chatUsers_model->deleteMembers($chatID);
                        $this->message_model->deleteMessagesForChat($chatID);
                    }
                    $username = $this->account_model->getUsernameById($userID);
                    $message_text = $username . ' left chat';
                    $this->message_model->saveMessage($chatID, $message_text, 0);
                    $this->response->body(array('msg' => 'success', 'code' => '10001'));
                }
            }
        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

    public function action_markMessageAsRead()
    {
        if ($this->request->jpost("chatID") && $this->request->jpost("SSID") && $this->request->jpost("messagesID")) {

            $SSID = $this->request->jpost("SSID");
            $userID = $this->session_model->getUserIdBySession($SSID);

            if ($userID == 0)
                return $this->response->body(array('msg' => 'SSID not found', 'code' => '00006'));

            $chatID = $this->request->jpost("chatID");

            if (sizeof($this->chat_model->getChatById($chatID)) < 1)
                return $this->response->body(array('msg' => 'Chat not found', 'code' => '00008'));


            $messagesID = $this->request->jpost("messagesID");

            if ($this->chatUsers_model->isMemberChat($userID, $chatID)) {
                if ($this->userMessages_model->markAsRead($chatID, $userID, $messagesID)) {
                    $this->response->body(array('msg' => 'success', 'code' => '10001'));
                } else {
                    return $this->response->body(array('msg' => 'Error: there is no messages with this user or messagesID', 'code' => '00011'));
                }
            } else {
                $this->response->body(array('msg' => 'This user is not a member in chat', 'code' => '00009'));
            }

        } else {
            $this->response->body(array('msg' => 'data not found', 'code' => '00003'));
        }
    }

}