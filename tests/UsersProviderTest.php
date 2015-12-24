<?php

use Mockery as m;

class UsersProviderTest extends TestCase
{

    public function teardown()
    {
        m::close();
    }

    /**
     * @test
     * @param $options
     * @dataProvider providerGetUsers
     */
    function getUsers($options)
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $collection = $this->makeUsersCollection($options);

        if($options['type'] !== 'all')
            if($options['type'] == 'online')
                $model->shouldReceive('where')
                    ->with('status', '=', true)
                    ->once()
                    ->andReturn($model)
                    ->ordered();
            else
                $model->shouldReceive('where')
                    ->with('status', '=', false)
                    ->once()
                    ->andReturn($model)
                    ->ordered();


        if(is_array($options['sort']))
            foreach ($options['sort'] as $field => $flag ) {
                $model->shouldReceive('orderBy')
                    ->with($field, $flag)
                    ->andReturn($model)
                    ->ordered();
            }

        $model->shouldReceive('skip')
            ->with($options['offset'])
            ->once()
            ->andReturn($model)
            ->ordered();

        $model->shouldReceive('take')
            ->with($options['limit'])
            ->once()
            ->andReturn($model)
            ->ordered();

        $model->shouldReceive('get')
            ->with($options['fields'])
            ->once()
            ->andReturn($collection)
            ->ordered();

        $result = $provider->getUsers($options);
        $this->assertEquals($collection, $result);

    }

    function providerGetUsers()
    {
        return [
            [
                [
                    'limit' => 25,
                    'offset' => 60,
                    'sort' => ['email'],
                    'fields' => ['*'],
                    'type' => 'all',

                ],

                [
                    'limit' => 10,
                    'offset' => 0,
                    'sort' => ['date'],
                    'fields' => ['username', 'status'],
                    'type' => 'online'
                ],

                [
                    'limit' => 2,
                    'offset' => 0,
                    'sort' => ['username', 'date'],
                    'fields' => ['*'],
                    'type' => 'offline'
                ]
            ],
        ];
    }

    /**
     * @test
     */
    function getUser()
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $id = 2;
        $options = [
            'fields' => ['*']
        ];
        $user = $this->makeUser($id, $options);

        $model
            ->shouldReceive('find')
            ->with($id, $options['fields'])
            ->once()
            ->andReturn($user);

        $result = $provider->getUser($id, $options);
        $this->assertEquals($user, $result);
    }


    /**
     * @test
     */
    function getUserFailUserNotFoundException()
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $id = 2;
        $options = [
            'fields' => ['*']
        ];

        $model
            ->shouldReceive('find')
            ->with($id, $options['fields'])
            ->once()
            ->andReturn(false);
        try {
            $provider->getUser($id, $options);
        } catch (\App\Exceptions\Api\UserNotFoundException $e) {
            $this->assertTrue(true);
        }

    }


    /**
     * @test
     */
    function createUser()
    {

        list($provider, $model) = $this->createProvidersAndModels();

        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id, 'status' => false]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => $user['password']
        ];

        $model
            ->shouldReceive('create')
            ->with($credentials)
            ->once()
            ->andReturn($model);

        $user = $provider->createUser($credentials);
        $this->assertEquals($model, $user);
    }

    /**
     * @test
     */
    function createUserFail()
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id, 'status' => false]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => $user['password']
        ];

        $model->shouldReceive('create')
            ->with($credentials)
            ->once()
            ->andReturn(false);

        try {
            $provider->createUser($credentials);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     */
    function updateUser()
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $id = 2;
        $credentials = ['status' => true ];
        $foundUser = m::mock(App\Models\User::class.'[save,update]');

        $model
            ->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn($foundUser);

        $foundUser->shouldReceive('update')
            ->with($credentials)
            ->once()
            ->andReturn($foundUser);

        $result = $provider->updateUser($id, $credentials);

        $this->assertEquals($foundUser, $result);
    }

    /**
     * @test
     */
    function updateUserFailUserNotFoundException()
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $id = 2;
        $credentials = ['status' => true ];
        $foundUser = m::mock(App\Models\User::class.'[save,update]');

        $model
            ->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn(false);

        $foundUser->shouldReceive('update')
            ->with($credentials)
            ->never();

        try {
            $provider->updateUser($id, $credentials);
        } catch (\App\Exceptions\Api\UserNotFoundException $e ) {
            $this->assertTrue(true);
        }

    }

    /**
    * @test
    */
    function deleteUser()
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $id = 3;
        $foundUser = m::mock(App\Models\User::class.'[save, update, delete]');

        $model
            ->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn($foundUser);

        $foundUser
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $result = $provider->deleteUser($id);
        $this->assertEquals(true, $result);
    }

    /**
     * @test
     */
    function deleteUserNotFound()
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $id = 3;
        $foundUser = m::mock(App\Models\User::class.'[save, update, delete]');

        $model
            ->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn(null);

        $foundUser
            ->shouldReceive('delete')
            ->with()
            ->never();
        try {
            $result = $provider->deleteUser($id);
            $this->assertEquals(false, $result);
        } catch ( \App\Exceptions\Api\UserNotFoundException $e) {
            $this->assertTrue(true);
        }

    }

    /**
     * @test
     */
    function deleteUserException()
    {
        list($provider, $model) = $this->createProvidersAndModels();

        $id = 3;
        $foundUser = m::mock(App\Models\User::class.'[save, update, delete]');

        $model
            ->shouldReceive('find')
            ->with($id)
            ->once()
            ->andReturn($foundUser);

        $foundUser
            ->shouldReceive('delete')
            ->with()
            ->once()
            ->andThrow(new \Exception);
        try {
            $result = $provider->deleteUser($id);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

    }

    function makeUser($id, $options = [], $params = [])
    {
        $rewritableFields = array_merge(['id' => $id], $params);

        $user = factory(App\Models\User::class)->make($rewritableFields);

        if(!empty($options) && $options['fields'][0] !== '*')
            foreach ($user as $field => $value) {
                if(!in_array($field, $options['fields'])) {
                    unset($user[$field]);
                }
            }
        return $user;
    }


    function createProvidersAndModels()
    {
        $userModel = m::mock('User', \App\Models\User::class . '[save, create, update]');
        $this->app->instance('User', $userModel);
        $messagesProvider = m::mock(\App\Providers\Api\MessagesProviderInterface::class);
        $usersProvider = new \App\Providers\Api\UsersProvider($userModel, $messagesProvider);

        return [$usersProvider, $userModel, $messagesProvider];
    }


    /**
     * @param $options
     * @return mixed
     */
    function makeUsersCollection($options)
    {
        $i = 1 + $options['offset'];
        $status = (isset($options['type'])) ? ['status' => $options['type']] : [];

        $models = factory(App\Models\User::class, $options['limit'])
            ->make($status)
            ->each(function($user) use(&$i)
                {
                    $user->id = $i++;
                });

        $collection = collect($models->toArray());
        if(is_array($options['sort']) && !empty($options['sort'])) {
            list($field, $flag) = head($options['sort']);
            if('asc' === $flag)
                $collection = $collection->sortBy($field);
            else
                $collection = $collection->sortByDesc($field);
        }

        $collection = $collection->map(
                function($user, $index)
                    use($options)
                {
                    if($options['fields'][0] !== '*')
                        foreach ($user as $field => $value) {
                            if(!in_array($field, $options['fields'])) {
                                unset($user[$field]);
                            }
                        }
                    return $user;
                }
            );
        return $collection;
    }
}
