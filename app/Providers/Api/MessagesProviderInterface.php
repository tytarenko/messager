<?php
namespace App\Providers\Api;


interface MessagesProviderInterface
{

    public function getMessages($user_id, $options);

    public function getMessage($user_id, $message_id, $options);

    public function createMessage($sender_id, array $data, $receiver_id = []);

    public function updateMessage($user_id, $message_id, $data);

    public function deleteMessage($user_id, $message_id);
}