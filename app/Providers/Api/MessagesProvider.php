<?php
namespace App\Providers\Api;

use App\Exceptions\Api\MessageDoesNotBelongToAUserException;
use App\Exceptions\Api\MessageNotFoundException;
use App\Exceptions\Api\UserNotFoundException;
use App\Models\Message;
use App\Models\User;

class MessagesProvider extends ApiProvider implements MessagesProviderInterface
{
    protected $message;
    protected $fields = ['*'];
    protected $type = 'all';
    protected $limit = 50;
    protected $unread = false;

    /**
     * @param Message $message
     * @param User $user
     */
    public function __construct(
        Message $message,
        User $user
    )
    {
        $this->message = $message;
        $this->user = $user;
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);

        if(!$this->sort) {
            $this->sort['date'] = 'desc';
        }

        if(isset($options['unread']) && $options['unread'] == true)
            $this->unread = true;
    }

    /**
     * @param $user_id
     * @param $options
     * @return mixed
     * @throws UserNotFoundException
     */
    public function getMessages($user_id, $options)
    {
        // check if user exist
        $this->userExists($user_id);

        // set input options
        $this->setOptions($options);

        // make filter
        $closure = $this->getMessagesFilter($user_id, $this->type);

        // set filter
        $this->message->where($closure);

        // if need unread messages
        if($this->unread) {
            $this->message->where('read', '=', false);
        }

        // sort
        if($this->sort) {
            // sort by user id
            if(isset($this->sort['user'])) {
                if($this->type == 'inbox') {
                    $this->message->orderBy('sender_id', $this->sort['user']);
                } elseif ($this->type == 'sent') {
                    $this->message->orderBy('receiver_id', $this->sort['user']);
                }
            }
            if(isset($this->sort['data'])) {
                $this->message->orderBy('created_at', $this->sort['data']);
            }
        }

        // set offset
        if($this->offset > 0)
            $this->message
                ->skip($this->offset);

        // get messages collection with fields
        $messages = $this->message
            ->take($this->limit)
            ->get($this->fields);

        return $messages;
    }


    /**
     * @param $user_id
     * @param $type
     * @return \Closure
     */
    protected function getMessagesFilter($user_id, $type)
    {
        return function($query)
            use ($user_id, $type)
        {
            switch($this->type) {
                case 'all':
                    $query->where('sender_id', '=', $user_id)
                        ->orWhere('receiver_id', '=', $user_id);
                    break;

                case 'inbox':
                    $query->where('receiver_id', '=', $user_id);
                    break;

                case 'sent':
                    $query->where('sender_id', '=', $user_id);
            }
        };
    }

    /**
     * @param $user_id
     * @param $message_id
     * @param $options
     * @return mixed
     * @throws MessageDoesNotBelongToAUserException
     * @throws MessageNotFoundException
     * @throws UserNotFoundException
     */
    public function getMessage($user_id, $message_id, $options)
    {
        // check if user exist
        $this->userExists($user_id);

        // set options
        $this->setOptions($options);

        // if find message
        if($message = $this->message->find($message_id, $this->fields)) {
            // check if message belong to user
            if( !in_array($user_id, [ $message->receiver_id, $message->sender_id ])) {
                throw new MessageDoesNotBelongToAUserException(
                    'The message with ID: '.$message_id.' does not belong to user with ID: '.$user_id
                );
            }
            // return message
            return $message;
        }
        throw new MessageNotFoundException('A message with ID: '.$message_id.' not found');
    }

    /**
     * @param $sender_id
     * @param array $data
     * @param null $receiver_id
     * @return static
     * @throws UserNotFoundException
     * @throws \Exception
     */
    public function createMessage($sender_id, array $data, $receiver_id = null)
    {

        if(is_null($receiver_id) && !isset($data['receiver_id']))
            throw new \InvalidArgumentException(
                'Passed receiver ID :' . $data['receiver_id'] . 'must be number and be greater than 0'
            );
        if(is_numeric($receiver_id) && !isset($data['receiver_id']))
            $data['receiver_id'] = $receiver_id;

        $data['sender_id'] = $sender_id;

        $this->userExists($data['sender_id']);

        $this->userExists($data['receiver_id']);

        if($message = $this->message->create($data))
            return $message;
        else
            throw new \Exception('1');
    }

    /**
     * @param $user_id
     * @param $message_id
     * @param $data
     * @return mixed
     * @throws MessageDoesNotBelongToAUserException
     * @throws MessageNotFoundException
     * @throws UserNotFoundException
     */
    public function updateMessage($user_id, $message_id, $data)
    {
        $this->userExists($user_id);

        if($message = $this->message->find($message_id)) {
            if( $message->receiver_id !== $user_id) {
                throw new MessageDoesNotBelongToAUserException(
                    'The message with ID: '.$message_id.' does not belong to user with ID: '.$user_id
                );
            }
            $message->update($data);
            return $message;
        } else {
            throw new MessageNotFoundException('A message with ID: '.$message_id.' not found');
        }
    }

    /**
     * @param $user_id
     * @param $message_id
     * @return bool|null
     * @throws MessageDoesNotBelongToAUserException
     * @throws \Exception
     */
    public function deleteMessage($user_id, $message_id)
    {
        $this->userExists($user_id);

        if($message = $this->message->find($message_id)) {

            if($message->receiver_id == $user_id) {
                $message->update(['receiver_id' => null]);
            } elseif($message->sender_id == $user_id) {
                $message->update(['sender_id' => null]);
            }

            if( $message->receiver_id == null && $message->sender_id == null) {
                if(!$message->delete())
                    throw new MessageDoesNotBelongToAUserException(
                        'The message with ID: '.$message_id.' does not belong to user with ID: '.$user_id
                    );
            }
            return true;
        } else
            throw new MessageNotFoundException('A message with ID: '.$message_id.' not found');
    }


    protected function userExists($user_id)
    {
        if(!$this->user->find($user_id))
            throw new UserNotFoundException('A user with ID: '.$user_id.' not found');
    }
}