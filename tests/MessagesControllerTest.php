<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;

class MessagesControllerTest extends TestCase
{
    protected $messagesProvider;

    public function setUp()
    {
        parent::setUp();
        $this->messagesProvider = m::mock(\App\Providers\Api\MessagesProviderInterface::class);
        $this->app->instance(App\Providers\Api\MessagesProviderInterface::class, $this->messagesProvider);
    }


    /**
     * @test
     * @dataProvider providerIndex
     * @param $query
     * @param $options
     */
    function index($query, $options)
    {
        $user_id = 1;
        $url = '/api/v1/users/'.$user_id.'/messages/'.$query;
        $collection = collect([]);

        $this->messagesProvider
            ->shouldReceive('getMessages')
            ->with($user_id, $options)
            ->once()
            ->andReturn($collection);

        $response = $this->get($url);
        $response->assertResponseStatus(200);
        $response->seeJsonEquals($collection->toArray());
    }


    function providerIndex()
    {
        return [
            [
                '',
                [
                    'limit' => 50,
                    'offset' => 0,
                    'sort' => null, // asc desc
                    'fields' => ['*'],
                    'status' => false,
                    'type' => 'all'
                ]
            ],

            [
                '?status=unread&type=inbox',
                [
                    'limit' => 50,
                    'offset' => 0,
                    'sort' => null, // asc desc
                    'fields' => ['*'],
                    'status' => false,
                    'type' => 'inbox'
                ]
            ]
        ];
    }


    /**
     * @test
     */
    function indexFailUserNotFoundException()
    {
        $user_id = 1;
        $url = '/api/v1/users/'.$user_id.'/messages/';

        $options = [
            'limit' => 50,
            'offset' => 0,
            'sort' => null, // asc desc
            'fields' => ['*'],
            'status' => false,
            'type' => 'all'
        ];

        $this->messagesProvider
            ->shouldReceive('getMessages')
            ->with($user_id, $options)
            ->once()
            ->andThrow(new \App\Exceptions\Api\UserNotFoundException(
                'A user with ID: '.$user_id.' not found'
            ));

        $this->get($url);
        $this->assertEquals(404, $this->response->status());
    }


    /**
     * @test
     */
    function indexFailInternalServerError()
    {
        $user_id = 1;
        $url = '/api/v1/users/'.$user_id.'/messages/';

        $options = [
            'limit' => 50,
            'offset' => 0,
            'sort' => null, // asc desc
            'fields' => ['*'],
            'read' => false,
            'type' => 'all'
        ];

        $this->messagesProvider
            ->shouldReceive('getMessages')
            ->with($user_id, $options)
            ->andThrow( new \Exception());

        $this->get($url);
        $this->assertEquals(500, $this->response->status());
    }


    /**
     * @test
     */
    function store()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id.'/messages/';
        $credentials = [
            'receiver_id' => 3,
            'subject' => str_random(),
            'body' => str_random(255)
        ];

        $createdMessage = factory(App\Models\Message::class)->make(
            array_merge($credentials, ['id' => $message_id, 'sender_id' => $user_id])
        );

        $this->messagesProvider
            ->shouldReceive('createMessage')
            ->with($user_id, $credentials)
            ->once()
            ->andReturn($createdMessage);

        $this->call('POST', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(200, $this->response->status());
        $this->assertEquals($createdMessage->toArray(), json_decode($this->response->content(), true));
    }

    /**
     * @test
     */
    function storeFailUserNotFoundException()
    {
        $user_id = 1;
        $url = '/api/v1/users/'.$user_id.'/messages/';
        $credentials = [
            'receiver_id' => 3,
            'subject' => str_random(),
            'body' => str_random(255)
        ];

        $this->messagesProvider
            ->shouldReceive('createMessage')
            ->with($user_id, $credentials)
            ->once()
            ->andThrow(new \App\Exceptions\Api\UserNotFoundException(
                'A user with ID: '.$user_id.' not found'
            ));

        $this->call('POST', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(404, $this->response->status());
    }

    /**
     * @test
     */
    function storeFailBadRequestExceptionUserId()
    {
        $user_id = str_random(3);
        $url = '/api/v1/users/'.$user_id.'/messages/';
        $credentials = [
            'receiver_id' => 3,
            'subject' => str_random(),
            'body' => str_random(255)
        ];

        $this->messagesProvider
            ->shouldReceive('createMessage')
            ->with($user_id, $credentials)
            ->never();

        $this->call('POST', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $this->response->status());
    }


    /**
     * @test
     */
    function storeFailBadRequestExceptionCredentials()
    {
        $user_id = 1;
        $url = '/api/v1/users/'.$user_id.'/messages/';
        $credentials = [
            'receiver_id' => str_random(3),
            'subject' => str_random(),
            'body' => str_random(255)
        ];

        $this->messagesProvider
            ->shouldReceive('createMessage')
            ->with($user_id, $credentials)
            ->never();

        $this->call('POST', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * @test
     */
    function storeFailInvalidArgumentException()
    {
        $user_id = -1;
        $url = '/api/v1/users/'.$user_id.'/messages/';
        $credentials = [
            'receiver_id' => str_random(3),
            'subject' => str_random(),
            'body' => str_random(255)
        ];

        $this->messagesProvider
            ->shouldReceive('createMessage')
            ->with($user_id, $credentials)
            ->never();

        $this->call('POST', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $this->response->status());
    }


    /**
     * @test
     */
    function storeFailInternalServerError()
    {
        $user_id = 2;
        $url = '/api/v1/users/'.$user_id.'/messages/';
        $credentials = [
            'receiver_id' => 1,
            'subject' => str_random(),
            'body' => str_random(255)
        ];

        $this->messagesProvider
            ->shouldReceive('createMessage')
            ->with($user_id, $credentials)
            ->andThrow(new \Exception());

        $this->call('POST', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(500, $this->response->status());
    }

    /**
     * @test
     */
    function show()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id.'/messages/'.$message_id.'/';

        $options = [
            'fields' => ['*']
        ];

        $message = factory(App\Models\Message::class)->make([
            'sender_id' => 3,
            'receiver_id' => $user_id
        ]);

        $this->messagesProvider
            ->shouldReceive('getMessage')
            ->with($user_id, $message_id, $options)
            ->once()
            ->andReturn($message);

        $response = $this->get($url);
        $response->assertResponseStatus(200);
        $response->seeJsonEquals($message->toArray());
    }

    /**
     * @test
     */
    function showFailUserNotFound()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id.'/messages/'.$message_id.'/';

        $options = [
            'fields' => ['*']
        ];

        $shouldResponse = [
            'code' => 404,
            'message' => 'A user with ID: '.$user_id.' not found'
        ];

        $this->messagesProvider
            ->shouldReceive('getMessage')
            ->with($user_id, $message_id, $options)
            ->andThrow(new \App\Exceptions\Api\UserNotFoundException(
                'A user with ID: '.$user_id.' not found'
            ));

        $response = $this->get($url);
        $response->assertResponseStatus(404);
        $response->seeJsonEquals($shouldResponse);
    }

    /**
     * @test
     */
    function showFailMessageNotFoundException()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id.'/messages/'.$message_id.'/';

        $options = [
            'fields' => ['*']
        ];

        $shouldResponse = [
            'code' => 404,
            'message' => 'A message with ID: '.$message_id.' not found'
        ];

        $this->messagesProvider
            ->shouldReceive('getMessage')
            ->with($user_id, $message_id, $options)
            ->andThrow(new \App\Exceptions\Api\MessageNotFoundException(
                'A message with ID: '.$message_id.' not found'
            ));

        $response = $this->get($url);
        $response->assertResponseStatus(404);
        $response->seeJsonEquals($shouldResponse);
    }

    /**
     * @test
     */
    function showFailMessageDoesNotBelongToAUserException()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id.'/messages/'.$message_id.'/';

        $options = [
            'fields' => ['*']
        ];

        $shouldResponse = [
            'code' => 403,
            'message' => 'The message with ID: '.$message_id.' does not belong to user with ID: '.$user_id
        ];

        $this->messagesProvider
            ->shouldReceive('getMessage')
            ->with($user_id, $message_id, $options)
            ->andThrow(new \App\Exceptions\Api\MessageDoesNotBelongToAUserException(
                'The message with ID: '.$message_id.' does not belong to user with ID: '.$user_id
            ));

        $response = $this->get($url);
        $response->assertResponseStatus(403);
        $response->seeJsonEquals($shouldResponse);
    }

    /**
     * @test
     */
    function showFailBadRequestException()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id.'/messages/'.$message_id.'/';

        $options = [
            'fields' => ['*']
        ];

        $shouldResponse = [
            'code' => 400,
            'message' => 'Passed ID ' . $user_id . ' , ' . $message_id . ' must be a number'
        ];

        $this->messagesProvider
            ->shouldReceive('getMessage')
            ->with($user_id, $message_id, $options)
            ->andThrow(new \App\Exceptions\Api\BadRequestException(
                'Passed ID ' . $user_id . ' , ' . $message_id . ' must be a number'
            ));

        $response = $this->get($url);
        $response->assertResponseStatus(400);
        $response->seeJsonEquals($shouldResponse);
    }


    /**
     * @test
     */
    function showFailInternalServerError()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id.'/messages/'.$message_id.'/';

        $options = [
            'fields' => ['*']
        ];

        $shouldResponse = [
            'code' => 500,
            'message' => 'Internal server error'
        ];

        $this->messagesProvider
            ->shouldReceive('getMessage')
            ->with($user_id, $message_id, $options)
            ->andThrow(new \Exception());

        $response = $this->get($url);
        $response->assertResponseStatus(500);
        $response->seeJsonEquals($shouldResponse);
    }


    /**
     * @test
     */
    function update()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;
        $credentials = [
            'read' => true,
        ];

        $updatedMessage = factory(App\Models\Message::class)->make(
            array_merge($credentials, ['id' => $message_id])
        );

        $this->messagesProvider
            ->shouldReceive('updateMessage')
            ->with($user_id, $message_id, $credentials)
            ->once()
            ->andReturn($updatedMessage);

        $this->call('PUT', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(200, $this->response->status());
        $this->assertEquals($updatedMessage->toArray(), json_decode($this->response->content(), true));
    }



    /**
     * @test
     */
    function updateFailBadRequestExceptionUserIdMessageId()
    {
        $user_id = str_random(3);
        $message_id = str_random(4);
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;
        $credentials = [
            'read' => true,
        ];

        $this->messagesProvider
            ->shouldReceive('updateMessage')
            ->with($user_id, $message_id, $credentials)
            ->never();

        $this->call('PUT', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * @test
     */
    function updateFailBadRequestExceptionCredentials()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;
        $credentials = [
            'read' => str_random(3),
        ];

        $this->messagesProvider
            ->shouldReceive('updateMessage')
            ->with($user_id, $message_id, $credentials)
            ->never();

        $this->call('PUT', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(400, $this->response->status());
    }


    /**
     * @test
     */
    function updateFailMessageDoesNotBelongToAUserException()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;
        $credentials = [
            'read' => true,
        ];

        $this->messagesProvider
            ->shouldReceive('updateMessage')
            ->with($user_id, $message_id, $credentials)
            ->andThrow( new \App\Exceptions\Api\MessageDoesNotBelongToAUserException(
                'The message with ID: '.$message_id.' does not belong to user with ID: '.$user_id
            ));

        $this->call('PUT', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(403, $this->response->status());
    }

    /**
     * @test
     */
    function updateFailUserNotFoundException()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;
        $credentials = [
            'read' => true,
        ];

        $this->messagesProvider
            ->shouldReceive('updateMessage')
            ->with($user_id, $message_id, $credentials)
            ->andThrow( new \App\Exceptions\Api\UserNotFoundException(
                'A user with ID: '.$user_id.' not found'
            ));

        $this->call('PUT', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(404, $this->response->status());
    }


    /**
     * @test
     */
    function updateFailInternalServerError()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;
        $credentials = [
            'read' => true,
        ];

        $this->messagesProvider
            ->shouldReceive('updateMessage')
            ->with($user_id, $message_id, $credentials)
            ->andThrow( new \Exception());

        $this->call('PUT', $url, [], [], [], [], json_encode($credentials));
        $this->assertEquals(500, $this->response->status());
    }

    /**
     * @test
     */
    function destroy()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;

        $this->messagesProvider
            ->shouldReceive('deleteMessage')
            ->with($user_id, $message_id)
            ->once()
            ->andReturn(true);

        $this->delete($url);
        $this->assertEquals(204, $this->response->status());
    }

    /**
     * @test
     */
    function destroyBadRequestException()
    {
        $user_id = str_random(3);
        $message_id = str_random(3);
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;

        $this->messagesProvider
            ->shouldReceive('deleteMessage')
            ->with($user_id, $message_id)
            ->never();

        $this->delete($url);
        $this->assertEquals(400, $this->response->status());
    }

    /**
     * @test
     */
    function destroyMessageNotFoundException()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;

        $this->messagesProvider
            ->shouldReceive('deleteMessage')
            ->with($user_id, $message_id)
            ->once()
            ->andThrow( new \App\Exceptions\Api\MessageNotFoundException(
                'A message with ID: '.$message_id.' not found'
            ));

        $this->delete($url);
        $this->assertEquals(404, $this->response->status());
    }
    /**
     * @test
     */
    function destroyMessageDoesNotBelongToAUserException()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;

        $this->messagesProvider
            ->shouldReceive('deleteMessage')
            ->with($user_id, $message_id)
            ->once()
            ->andThrow(new \App\Exceptions\Api\MessageDoesNotBelongToAUserException(
                'The message with ID: '.$message_id.' does not belong to user with ID: '.$user_id
            ));

        $this->delete($url);
        $this->assertEquals(403, $this->response->status());
    }

    /**
     * @test
     */
    function destroyUserNotFoundException()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;

        $this->messagesProvider
            ->shouldReceive('deleteMessage')
            ->with($user_id, $message_id)
            ->once()
            ->andThrow(new \App\Exceptions\Api\UserNotFoundException(
                'A user with ID: '.$user_id.' not found'
            ));

        $this->delete($url);
        $this->assertEquals($this->response->status(), 404);
    }

    /**
     * @test
     */
    function destroyInternalServerError()
    {
        $user_id = 1;
        $message_id = 2;
        $url = '/api/v1/users/'.$user_id .'/messages/'.$message_id;

        $this->messagesProvider
            ->shouldReceive('deleteMessage')
            ->with($user_id, $message_id)
            ->once()
            ->andThrow(new \Exception('A user with ID: '.$user_id.' not found'));

        $this->delete($url);
        $this->assertEquals(500, $this->response->status());
    }
}
