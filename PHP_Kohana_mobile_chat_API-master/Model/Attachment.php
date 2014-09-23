<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Attachment extends ORM
{
    protected $_table_name = 'attachments';
    protected $_primary_key = 'id';

    protected $_belongs_to = array(
        'message' => array(
            'model' => 'Message',
            'foreign_key' => 'messageID',
        ),
    );

    public function saveAttachment($name,$expansion,$messageID) {
        $this->set('name',$name)
            ->set('extension',$expansion)
            ->set('messageID',$messageID)
            ->create();
        return $this->id;
    }
}