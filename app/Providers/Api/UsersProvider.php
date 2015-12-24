<?php namespace App\Providers\Api;

use App\Exceptions\Api\UserNotFoundException;
use App\Models\User;

class UsersProvider extends ApiProvider implements UsersProviderInterface
{
    /**
     * @var User
     */
    protected $user;
    /**
     * @var MessagesProviderInterface
     */
    protected $messagesProvider;

    /**
     * @var int
     */
    protected $limit = 25;
    /**
     * @var bool
     */
    protected $withMessages = false;

    /**
     * @param User $user
     * @param MessagesProviderInterface $messagesProvider
     */
    public function __construct(
        User $user,
        MessagesProviderInterface $messagesProvider
    )
    {
        $this->user = $user;
        $this->messagesProvider = $messagesProvider;
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);
    }

    /**
     * @param array $options
     * @return mixed
     */
    public function getUsers(array $options)
    {
        // set input options
        $this->setOptions($options);

        // filtration users
        if($this->type !== 'all')
            // if needs online users
            if($this->type == 'online')
                $this->user->where('status', '=', true);
            // if needs offline users
            else
                $this->user->where('status', '=', false);

        // sorted users
        if($this->sort) {
            if(isset($this->sort['date']))
                // sort by date added
                $this->user->orderBy('created_at', $this->sort['date']);

            // sort by username
            if(isset($this->sort['username']))
                $this->user->orderBy('username', $this->sort['username']);

            // sort by email
            if(isset($this->sort['email']))
                $this->user->orderBy('email', $this->sort['email']);
        }

        // set offset
        if($this->offset > 0)
            $this->user
                ->skip($this->offset);

        // get users collection with fields
        $users = $this->user
            ->take($this->limit)
            ->get($this->fields);

        // return collection
        return $users;
    }

    /**
     * @param $id
     * @param array $options
     * @return mixed
     * @throws \Exception
     */
    public function getUser($id, array $options = [])
    {
        // set options
        $this->setOptions($options);

        // if user exists
        if($user = $this->user->find($id, $this->fields))
            // return user
            return $user;
        else
            // throw exception
            throw new UserNotFoundException('A user with ID: '.$id.' not found');
    }

    /**
     * @param array $credentials
     * @return static
     * @throws \Exception
     */
    public function createUser(array $credentials)
    {
        // if user created
        if($user = $this->user->create($credentials)) {
            // return created user
            return $user;
        } else
            // throw exception
            throw new \Exception('Cannot create new user');
    }

    /**
     * @param $id
     * @param array $credentials
     * @return mixed
     * @throws \Exception
     */
    public function updateUser($id, array $credentials)
    {
        // if user exists
        if( $user = $this->user->find($id) ) {
            // update user
            $user->update($credentials);
            // return updated user
            return $user;
        } else
            // throw exception
            throw new UserNotFoundException('A user with ID: '.$id.' not found');
    }

    /**
     * @param $id
     * @return mixed
     * @throws UserNotFoundException
     */
    public function deleteUser($id)
    {
        // if user exists
        if($user = $this->user->find($id))
            // delete user
            return $user->delete();
        else
            // throw exception
            throw new UserNotFoundException('A user with ID: '.$id.' not found');
    }
}