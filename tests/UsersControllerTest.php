<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;

class UsersControllerTest extends TestCase
{
    protected $userProvider;

    public function setUp()
    {
        parent::setUp();
        $this->userProvider = m::mock(\App\Providers\Api\UsersProviderInterface::class);
        $this->app->instance(App\Providers\Api\UsersProviderInterface::class, $this->userProvider);
    }

    public function teardown()
    {
        m::close();
    }

    /**
     * @test
     * @dataProvider providerIndex
     * @param array $options
     * @param $query
     */
    function index($query, $options)
    {
        $collection = $this->makeUsersCollection(10);

        $this->userProvider
            ->shouldReceive('getUsers')
            ->with($options)
            ->once()
            ->andReturn($collection);

        $response = $this->get('/api/v1/users'.$query);
        $response->assertResponseStatus(200);
        $response->seeJsonEquals($collection->toArray());
    }


    function providerIndex()
    {
        return [
            [
                '?fields=id,username,email&type=all',
                [
                    'limit' => 10,
                    'offset' => 0,
                    'sort' => null,
                    'fields' => ['id', 'username', 'email'],
                    'type' => 'all'
                ]
            ],
            [
                '?fields=id,username,email&sort=username:desc,,id:asc&offset=60&limit=25&type=online',
                [
                    'limit' => 25,
                    'offset' => 60,
                    'sort' => ['username' => 'desc', 'id' => 'asc'],
                    'fields' => ['id', 'username', 'email'],
                    'type' => 'online'
                ]
            ],
            [
                '?fields=id,username,email&sort=username:desc,,id:asc&offset=&limit=25&type=offline',
                [
                    'limit' => 25,
                    'offset' => 0,
                    'sort' => ['username' => 'desc', 'id' => 'asc'],
                    'fields' => ['id', 'username', 'email'],
                    'type' => 'offline'
                ]
            ],
        ];
    }


    /**
     * @test
     */
    function store()
    {
        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id, 'status' => false]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => $user['password']
        ];

        $this->userProvider
            ->shouldReceive('createUser')
            ->with($credentials)
            ->once()
            ->andReturn($user);

        $this->call('POST', '/api/v1/users', [], [], [], [], json_encode($credentials));
        $this->assertEquals(201, $this->response->status());
        $this->assertEquals($user->toArray(), json_decode($this->response->content(), true));
    }


    /**
     * @test
     */
    function storeFailBadRequest()
    {
        $credentials = [
            'username' => str_random(512),
            'email' => str_random(),
            'password' => str_random(3)
        ];
        $this->userProvider
            ->shouldReceive('createUser')
            ->with($credentials)
            ->never();

        $response = $this->call('POST', '/api/v1/users', [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $response->getStatusCode());
    }
    
    /**
     * @test
     */
    function storeFailInternalServerError()
    {
        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id, 'status' => false]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => $user['password']
        ];
        $this->userProvider
            ->shouldReceive('createUser')
            ->with($credentials)
            ->once()
            ->andThrow(\Exception::class);

        $response = $this->call('POST', '/api/v1/users', [], [], [], [], json_encode($credentials));
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @test
     * @dataProvider providerShow
     * @param $params
     * @param $query
     */
    function show($query, $params)
    {
        $id = 2;
        $model = factory(App\Models\User::class)->make(['id' => $id, 'status' => false]);
        $this->userProvider
            ->shouldReceive('getUser')
            ->with($id, $params)
            ->once()
            ->andReturn($model);

        $response = $this->get('/api/v1/users/'.$id.$query);
        $response->assertResponseStatus(200);
        $response->seeJsonEquals($model->toArray());
    }

    function providerShow()
    {
        return [
            [
                '?fields=id,username,email',
                [
                    'fields' => ['id', 'username', 'email'],
                ]
            ],
            [
                '?fields=id,password',
                [
                    'fields' => ['id'],
                ]
            ],
            [
                '?fields',
                [
                    'fields' => ['*'],
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider providerShow
     * @param $params
     * @param $query
     */
    function showFailUserNonFound($query, $params)
    {

        $id = 2;
        $this->userProvider
            ->shouldReceive('getUser')
            ->with($id, $params)
            ->once()
            ->andThrow(\App\Exceptions\Api\UserNotFoundException::class);

        $response = $this->get('/api/v1/users/'.$id.$query);
        $response->assertResponseStatus(404);
    }

    /**
     * @test
     * @dataProvider providerShow
     * @param $params
     * @param $query
     */
    function showFailInternalServerError($query, $params)
    {
        $id = 2;
        $this->userProvider
            ->shouldReceive('getUser')
            ->with($id, $params)
            ->once()
            ->andThrow(\Exception::class);

        $response = $this->get('/api/v1/users/'.$id.$query);
        $response->assertResponseStatus(500);
    }
    /**
     * @test
     * @dataProvider providerShow
     * @param $params
     * @param $query
     */
    function showFailBadRequest($query, $params)
    {
        $id = 'e3f1';
        $this->userProvider
            ->shouldReceive('getUser')
            ->with($id, $params)
            ->never();

        $response = $this->get('/api/v1/users/'.$id.$query);
        $response->assertResponseStatus(400);
    }

    /**
     * @test
     */
    function updateByPutMethod()
    {
        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => $user['password']
        ];

        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->once()
            ->andReturn($user);

        $this->call('PUT', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(200, $this->response->status());
        $this->assertEquals($user->toArray(), json_decode($this->response->content(), true));
    }


    /**
     * @test
     */
    function updateByPutMethodWithCreateUser()
    {
        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => $user['password']
        ];

        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->once()
            ->andThrow(new \App\Exceptions\Api\UserNotFoundException(
                'A user with ID: '.$id.' not found'
            ));

        $this->userProvider
            ->shouldReceive('createUser')
            ->with($credentials)
            ->once()
            ->andReturn($user);

        $this->call('PUT', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(201, $this->response->status());
        $this->assertEquals($user->toArray(), json_decode($this->response->content(), true));
    }

    /**
     * @test
     */
    function updateByPutMethodFailBadRequestId()
    {
        $id = 'e3f1';
        $credentials = [
            'username' => str_random(512),
            'email' => str_random(),
            'password' => str_random(3)
        ];

        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->never();

        $response = $this->call('PUT', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    function updateByPutMethodFailBadRequestCredentials()
    {
        $id = 2;
        $credentials = [
            'username' => str_random(512),
            'email' => str_random(),
            'password' => str_random(3)
        ];

        $this->userProvider
            ->shouldReceive('userExists')
            ->with($id)
            ->never();

        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->never();

        $response = $this->call('PUT', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    function updateByPutMethodFailInternalServerError()
    {
        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email'],
            'password' => $user['password']
        ];

        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->once()
            ->andThrow(\Exception::class);

        $response = $this->call('PUT', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(500, $response->getStatusCode());
    }


    /**
     * @test
     */
    function updateByPatchMethod()
    {
        {
            $id = 2;
            $user = factory(App\Models\User::class)->make(['id' => $id]);
            $credentials = [
                'username' => $user['username'],
                'email' => $user['email']
            ];

            $this->userProvider
                ->shouldReceive('updateUser')
                ->with($id, $credentials)
                ->once()
                ->andReturn($user);

            $this->call('PATCH', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
            $this->assertEquals(200, $this->response->status());
            $this->assertEquals($user->toArray(), json_decode($this->response->content(), true));
        }
    }

    /**
     * @test
     */
    function updateByPatchMethodFailUserNotFound()
    {
        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email']
        ];

        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->andThrow(new \App\Exceptions\Api\UserNotFoundException(
                'A user with ID: '.$id.' not found'
            ));

        $this->call('PATCH', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(404, $this->response->status());
    }

    /**
     * @test
     */
    function updateByPatchMethodFailBadRequestId()
    {
        $id = 'e3f1';
        $credentials = [
            'username' => str_random(512),
            'email' => str_random(),
            'password' => str_random(3)
        ];

        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->never();

        $response = $this->call('PATCH', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    function updateByPatchMethodFailBadRequestCredentials()
    {
        $id = 2;
        $credentials = [
            'username' => str_random(512),
            'email' => str_random(),
            'password' => str_random(3)
        ];
        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->never();

        $response = $this->call('PATCH', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @test
     */
    function updateByPatchMethodFailInternalServerError()
    {
        $id = 2;
        $user = factory(App\Models\User::class)->make(['id' => $id]);
        $credentials = [
            'username' => $user['username'],
            'email' => $user['email']
        ];

        $this->userProvider
            ->shouldReceive('updateUser')
            ->with($id, $credentials)
            ->once()
            ->andThrow(\Exception::class);

        $response = $this->call('PATCH', '/api/v1/users/'.$id, [], [], [], [], json_encode($credentials));
        $this->assertEquals(500, $response->getStatusCode());
    }



    /**
     * @test
     */
    function destroy()
    {
        $id = 2;
        $this->userProvider
            ->shouldReceive('deleteUser')
            ->with($id)
            ->once()
            ->andReturn(true);

        $this->delete('/api/v1/users/'.$id);
        $this->assertEquals(204, $this->response->status());
    }

    /**
     * @test
     */
    function destroyFailUserNotFound()
    {
        $id = 2;
        $this->userProvider
            ->shouldReceive('deleteUser')
            ->with($id)
            ->once()
            ->andThrow(\App\Exceptions\Api\UserNotFoundException::class);

        $this->delete('/api/v1/users/'.$id);
        $this->assertEquals(404, $this->response->status());
    }


    /**
     * @test
     */
    function destroyFailBadRequest()
    {
        $id = 'e3f1';
        $this->userProvider
            ->shouldReceive('deleteUser')
            ->with($id)
            ->never();

        $this->delete('/api/v1/users/'.$id);
        $this->assertEquals(400, $this->response->getStatusCode());
    }

    /**
     * @test
     */
    function destroyFailInternalServerError()
    {
        $id = 2;
        $this->userProvider
            ->shouldReceive('deleteUser')
            ->with($id)
            ->once()
            ->andThrow(\Exception::class);

        $this->delete('/api/v1/users/'.$id);
        $this->assertEquals(500, $this->response->getStatusCode());
    }


    /**
     * @param $count
     * @return mixed
     */
    function makeUsersCollection($count)
    {
        $i = 1;
        $collection = factory(App\Models\User::class, $count)->make()->each(function($user) use(&$i){
            $user->id = $i++;
        });

        return $collection;
    }


}
