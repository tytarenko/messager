<?php

use Mockery as m;

class MessagesProviderTest extends TestCase
{

    function createProvidersAndModels()
    {
        $userModel = m::mock('User', \App\Models\User::class . '[save, create, update, delete]');
        $this->app->instance('User', $userModel);
        $messageModel = m::mock('Message', \App\Models\Message::class . '[save, create, update]');
        $this->app->instance('Message', $userModel);
        $messagesProvider = new \App\Providers\Api\MessagesProvider($messageModel, $userModel);

        return [$messagesProvider, $messageModel, $userModel];
    }


    /**
     * @test
     * @dataProvider providerGetMessages
     * @param $options
     */
    function getMessages($options)
    {
        list($messagesProvider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $collection = collect([]);

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $messageModel->shouldReceive('where')
            ->once()
            ->andReturn($messageModel)
            ->ordered();

        if(intval($options['offset']) > 0)
            $messageModel->shouldReceive('skip')
                ->with($options['offset'])
                ->once()
                ->andReturn($messageModel)
                ->ordered();

        $messageModel->shouldReceive('take')
            ->with($options['limit'])
            ->once()
            ->andReturn($messageModel)
            ->ordered();

        $messageModel->shouldReceive('get')
            ->with($options['fields'])
            ->once()
            ->andReturn($collection)
            ->ordered();

        $result = $messagesProvider->getMessages($user_id, $options);
        $this->assertEquals($collection, $result);
    }

    function providerGetMessages()
    {
        return [
            [
                [
                    'fields' => ['*'],
                    'limit' => 10,
                    'offset' => 0
                ]
            ]
        ];
    }


    /**
     * @test
     */
    function getMessage()
    {
        list($messagesProvider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;
        $messageModel->receiver_id = $user_id;

        $options = [
            'fields' => ['*']
        ];

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $messageModel
            ->shouldReceive('find')
            ->with($message_id, $options['fields'])
            ->once()
            ->andReturn($messageModel);

        $result = $messagesProvider->getMessage($user_id, $message_id, $options);
        $this->assertEquals($messageModel, $result);
    }

    /**
     * @test
     */
    function getMessageFailMessageDoesNotBelongToAUserException()
    {
        list($messagesProvider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;

        $options = [
            'fields' => ['*']
        ];

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $messageModel
            ->shouldReceive('find')
            ->with($message_id, $options['fields'])
            ->once()
            ->andReturn($messageModel);
        try {
            $messagesProvider->getMessage($user_id, $message_id, $options);
        } catch( \App\Exceptions\Api\MessageDoesNotBelongToAUserException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     */
    function getMessageFailMessageNotFound()
    {
        list($messagesProvider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;

        $options = [
            'fields' => ['*']
        ];

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $messageModel
            ->shouldReceive('find')
            ->with($message_id, $options['fields'])
            ->once()
            ->andReturn(false);
        try {
            $messagesProvider->getMessage($user_id, $message_id, $options);
        } catch( \App\Exceptions\Api\MessageNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     */
    function getMessageFailUserNotFound()
    {
        list($messagesProvider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;

        $options = [
            'fields' => ['*']
        ];

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn(false);

        $messageModel
            ->shouldReceive('find')
            ->with($message_id, $options['fields'])
            ->never();

        try {
            $messagesProvider->getMessage($user_id, $message_id, $options);
        } catch( \App\Exceptions\Api\UserNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     */
    function createMessage()
    {
        list($provider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $sender_id = 1;
        $receiver_id = 2;

        $message = factory(App\Models\Message::class)->make([
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id
        ]);

        $preparedData = array_only($message->toArray(), ['subject', 'body' ,'sender_id', 'receiver_id']);
        $data = array_only($preparedData, ['subject', 'body']);

        $userModel
            ->shouldReceive('find')
            ->with($sender_id)
            ->once()
            ->andReturn($userModel);

        $userModel
            ->shouldReceive('find')
            ->with($receiver_id)
            ->once()
            ->andReturn($userModel);

        $messageModel
            ->shouldReceive('create')
            ->with($preparedData)
            ->once()
            ->andReturn($message);

        $result = $provider->createMessage($sender_id, $data, $receiver_id);
        $this->assertEquals($message, $result);
    }

    /**
     * @test
     */
    function createMessageFail()
    {
        list($provider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $sender_id = 1;
        $receiver_id = 2;

        $message = factory(App\Models\Message::class)->make([
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id
        ]);

        $preparedData = array_only($message->toArray(), ['subject', 'body' ,'sender_id', 'receiver_id']);
        $data = array_only($preparedData, ['subject', 'body']);

        $userModel
            ->shouldReceive('find')
            ->with($sender_id)
            ->once()
            ->andReturn($userModel);

        $userModel
            ->shouldReceive('find')
            ->with($receiver_id)
            ->once()
            ->andReturn($userModel);

        $messageModel
            ->shouldReceive('create')
            ->with($preparedData)
            ->once()
            ->andReturn(false);

        try {
            $provider->createMessage($sender_id, $data, $receiver_id);
        } catch (\Exception $e ) {
            $this->assertTrue(true);
        }

    }


    /**
     * @test
     */
    function createMessageFailSenderUserNotFound()
    {
        list($provider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $sender_id = 1;
        $receiver_id = 2;

        $message = factory(App\Models\Message::class)->make([
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id
        ]);

        $preparedData = array_only($message->toArray(), ['subject', 'body' ,'sender_id', 'receiver_id']);
        $data = array_only($preparedData, ['subject', 'body']);

        $userModel
            ->shouldReceive('find')
            ->with($sender_id)
            ->once()
            ->andReturn($userModel);

        $userModel
            ->shouldReceive('find')
            ->with($receiver_id)
            ->once()
            ->andReturn(false);

        $messageModel
            ->shouldReceive('create')
            ->with($preparedData)
            ->never();

        try {
            $provider->createMessage($sender_id, $data, $receiver_id);
        } catch(\App\Exceptions\Api\UserNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     */
    function createMessageFailReceiverUserNotFound()
    {
        list($provider, $messageModel, $userModel) = $this->createProvidersAndModels();
        $sender_id = 1;
        $receiver_id = 2;

        $message = factory(App\Models\Message::class)->make([
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id
        ]);

        $preparedData = array_only($message->toArray(), ['subject', 'body' ,'sender_id', 'receiver_id']);
        $data = array_only($preparedData, ['subject', 'body']);

        $userModel
            ->shouldReceive('find')
            ->with($sender_id)
            ->once()
            ->andReturn(false);

        $userModel
            ->shouldReceive('find')
            ->with($receiver_id)
            ->never();

        $messageModel
            ->shouldReceive('create')
            ->with($preparedData)
            ->never();

        try {
            $provider->createMessage($sender_id, $data, $receiver_id);
        } catch(\App\Exceptions\Api\UserNotFoundException $e) {
            $this->assertTrue(true);
        }
    }


    /**
     * @test
     */
    function updateMessage()
    {
        list($provider, $model, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;

        $data = ['read' => true];
        $foundMessage = m::mock(App\Models\Message::class.'[save,update]');
        $foundMessage->receiver_id = $user_id;

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $model
            ->shouldReceive('find')
            ->with($message_id)
            ->once()
            ->andReturn($foundMessage);

        $foundMessage
            ->shouldReceive('update')
            ->with($data)
            ->once()
            ->andReturn($foundMessage);

        $result = $provider->updateMessage($user_id, $message_id, $data);
        $this->assertEquals($foundMessage, $result);
    }


    /**
     * @test
     */
    function updateMessageFailMessageDoesNotBelongToAUserException()
    {
        list($provider, $model, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;

        $data = ['read' => true];
        $foundMessage = m::mock(App\Models\Message::class.'[save,update]');

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $model
            ->shouldReceive('find')
            ->with($message_id)
            ->once()
            ->andReturn($foundMessage);

        $foundMessage
            ->shouldReceive('update')
            ->with($data)
            ->never();

        try {
            $provider->updateMessage($user_id, $message_id, $data);
        } catch( \App\Exceptions\Api\MessageDoesNotBelongToAUserException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     */
    function updateMessageFailMessageNotFound()
    {
        list($provider, $model, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;

        $data = ['read' => true];
        $foundMessage = m::mock(App\Models\Message::class.'[save,update]');

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $model
            ->shouldReceive('find')
            ->with($message_id)
            ->once()
            ->andReturn(false);

        $foundMessage
            ->shouldReceive('update')
            ->with($data)
            ->never();

        try {
            $provider->updateMessage($user_id, $message_id, $data);
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }


    /**
     * @test
     */
    function updateMessageFailUserNotFound()
    {
        list($provider, $model, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;

        $data = ['read' => true];
        $foundMessage = m::mock(App\Models\Message::class.'[save,update]');
        $foundMessage->receiver_id = $user_id;

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn(false);

        $model
            ->shouldReceive('find')
            ->with($message_id)
            ->never();

        $foundMessage
            ->shouldReceive('update')
            ->with($data)
            ->never();
        try {
            $provider->updateMessage($user_id, $message_id, $data);
        } catch (\App\Exceptions\Api\UserNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     */
    function deleteMessage()
    {
        list($provider, $model, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;
        $receiver = ['receiver_id' => null];
        $sender = ['sender_id' => null];

        $foundMessage = m::mock(App\Models\Message::class.'[save,update,delete]');
        $foundMessage->id = $message_id;
        $foundMessage->sender_id = null;
        $foundMessage->receiver_id = null;

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $model
            ->shouldReceive('find')
            ->with($message_id)
            ->once()
            ->andReturn($foundMessage);

        $foundMessage
            ->shouldReceive('update')
            ->with($receiver)
            ->andReturn($foundMessage);

        $foundMessage
            ->shouldReceive('update')
            ->with($sender)
            ->andReturn($foundMessage);

        $foundMessage
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $result = $provider->deleteMessage($user_id, $message_id);
        $this->assertEquals(true, $result);
    }

    /**
     * @test
     */
    function deleteMessageFailMessageMotFound()
    {
        list($provider, $model, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;
        $receiver = ['receiver_id' => null];
        $sender = ['sender_id' => null];

        $foundMessage = m::mock(App\Models\Message::class.'[save,update,delete]');
        $foundMessage->id = $message_id;
        $foundMessage->sender_id = null;
        $foundMessage->receiver_id = null;

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $model
            ->shouldReceive('find')
            ->with($message_id)
            ->once()
            ->andReturn(false);

        $foundMessage
            ->shouldReceive('update')
            ->with($receiver)
            ->never();

        $foundMessage
            ->shouldReceive('update')
            ->with($sender)
            ->never();

        $foundMessage
            ->shouldReceive('delete')
            ->never();

        try {
            $provider->deleteMessage($user_id, $message_id);
        } catch (\App\Exceptions\Api\MessageNotFoundException $e) {
            $this->assertTrue(true);
        }
    }


    /**
     * @test
     */
    function deleteMessageFailUserMotFound()
    {
        list($provider, $model, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;
        $receiver = ['receiver_id' => null];
        $sender = ['sender_id' => null];

        $foundMessage = m::mock(App\Models\Message::class.'[save,update,delete]');
        $foundMessage->id = $message_id;
        $foundMessage->sender_id = null;
        $foundMessage->receiver_id = null;

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn(false);

        $model
            ->shouldReceive('find')
            ->with($message_id)
            ->never();

        $foundMessage
            ->shouldReceive('update')
            ->with($receiver)
            ->never();

        $foundMessage
            ->shouldReceive('update')
            ->with($sender)
            ->never();

        $foundMessage
            ->shouldReceive('delete')
            ->never();

        try {
            $provider->deleteMessage($user_id, $message_id);
        } catch( \App\Exceptions\Api\UserNotFoundException $e) {
            $this->assertTrue(true);
        }
    }


    /**
     * @test
     */
    function deleteMessageDoesNotBelongToAUserException()
    {
        list($provider, $model, $userModel) = $this->createProvidersAndModels();
        $user_id = 1;
        $message_id = 2;
        $receiver = ['receiver_id' => null];
        $sender = ['sender_id' => null];

        $foundMessage = m::mock(App\Models\Message::class.'[save,update,delete]');
        $foundMessage->id = $message_id;
        $foundMessage->sender_id = null;
        $foundMessage->receiver_id = null;

        $userModel
            ->shouldReceive('find')
            ->with($user_id)
            ->once()
            ->andReturn($userModel);

        $model
            ->shouldReceive('find')
            ->with($message_id)
            ->once()
            ->andReturn($foundMessage);

        $foundMessage
            ->shouldReceive('update')
            ->with($receiver)
            ->never();

        $foundMessage
            ->shouldReceive('update')
            ->with($sender)
            ->never();

        $foundMessage
            ->shouldReceive('delete')
            ->once()
            ->andReturn(false);

        try {
            $result = $provider->deleteMessage($user_id, $message_id);
            $this->assertEquals(false, $result);
        } catch( \App\Exceptions\Api\MessageDoesNotBelongToAUserException $e) {
            $this->assertTrue(true);
        }
    }
}
