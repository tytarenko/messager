<?php namespace App\Http\Controllers\Api;

use App\Exceptions\Api\BadRequestException;
use App\Exceptions\Api\MessageDoesNotBelongToAUserException;
use App\Exceptions\Api\MessageNotFoundException;
use App\Exceptions\Api\UserNotFoundException;
use App\Providers\Api\MessagesProviderInterface;
use App\Providers\Api\UsersProviderInterface;
use Illuminate\Http\Request;
use \Illuminate\Validation\Factory as Validator;
use App\Http\Requests;

class MessagesController extends ApiController
{
    /**
     * @var int
     */
    protected $defaultLimit = 50;
    /**
     * @var bool
     */
    protected $defaultStatus = false;
    /**
     * @var array
     */
    protected $availableFields = ['id','sender_id','receiver_id','subject', 'body', 'read','created_at'];
    /**
     * @var array
     */
    protected $types = ['all', 'inbox', 'sent'];

    /**
     * @var MessagesProviderInterface
     */
    protected $messagesProvider;
    /**
     * @var Validator
     */
    protected $validator;
    /**
     * @var
     */
    protected $usersProvider;

    /**
     * MessagesController constructor.
     * @param MessagesProviderInterface $messagesProvider
     * @param UsersProviderInterface $usersProvider
     * @param Validator $validator
     */
    public function __construct(
        MessagesProviderInterface $messagesProvider,
        UsersProviderInterface $usersProvider,
        Validator $validator
    )
    {
        $this->messagesProvider = $messagesProvider;
        $this->userProvider = $usersProvider;
        $this->validator = $validator;
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $user_id)
    {
        try {

            // if input id is not numeric throw exception
            $this->checkNumericId($user_id);

            // set default options
            $defaultOptions = [
                'limit' => $this->defaultLimit,
                'offset' => $this->defaultOffset,
                'sort' => $this->defaultSort,
                'fields' => $this->defaultFields,
                'status' => $this->defaultStatus,
                'type' => $this->defaultType // all, inbox, sent
            ];

            // parse input raw options
            $parsedOptions = $this->parseOptions($request, $defaultOptions);

            // get collection of messages by options
            $messages = $this->messagesProvider->getMessages($user_id, $parsedOptions);

            // return collection in json format
            return response()->json($messages);

        } catch (UserNotFoundException $e) {
            // if user does not found return message
            return $this->userNotFound($e->getMessage());
        } catch (\Exception $e) {
            // if somethings is bad return message
            return $this->internalServerError();
        }
    }

    /**
     * @param Request $request
     * @param $user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $user_id)
    {
        try {
            // if input id is not numeric throw exception
            $this->checkNumericId($user_id);

            // get only declare credentials
            $credentials = array_only($request->json()->all(), ['receiver_id', 'subject', 'body']);

            // validate input credentials by rules
            $validator = $this->validator->make(
                $credentials,
                [
                    'receiver_id' => 'required|integer',
                    'subject' => 'required|string|max:255',
                    'body' => 'required|string'
                ]
            );

            // if not valid credentials throw exception
            if ($validator->fails()) {
                throw new BadRequestException($validator->errors());
            }
            // create message by credentials
            $message = $this->messagesProvider->createMessage($user_id, $credentials);

            // return message in json format
            return response()->json($message);

        } catch (UserNotFoundException $e) {
            // if user does not found return message
            return $this->userNotFound($e->getMessage());
        } catch (BadRequestException $e) {
            // if input credentials not valid return message
            return $this->badRequest($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            // if input credentials not valid return message
            return $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            // if somethings is bad return message
            return $this->internalServerError();
        }
    }

    /**
     * @param Request $request
     * @param $user_id
     * @param $message_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $user_id, $message_id)
    {
        try {
            // if input id is not numeric throw exception
            $this->checkNumericId($user_id, $message_id);

            // set default options
            $defaultOptions = [
                'fields' => ['*'],
            ];

            // parse input raw options
            $parsedOptions = $this->parseOptions($request, $defaultOptions);

            // get message by id and options
            $message = $this->messagesProvider->getMessage($user_id, $message_id, $parsedOptions);

            // return option in json format
            return response()->json($message);

        } catch (MessageNotFoundException $e) {
            // if message does not found return message
            return $this->messageNotFound($e->getMessage());
        } catch (MessageDoesNotBelongToAUserException $e) {
            // if a message does not belong to a user return message
            return $this->messageDoesNotBelongToAUserException($e->getMessage());
        } catch (UserNotFoundException $e) {
            // if user does not found return message
            return $this->userNotFound($e->getMessage());
        } catch (BadRequestException $e) {
            // if input data does not valid return message
            return $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            // if somethings is bad return message
            return $this->internalServerError();
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $user_id
     * @param $message_id
     * @return mixed
     */
    public function update(Request $request, $user_id, $message_id)
    {
        try {

            // if input id is not numeric throw exception
            $this->checkNumericId($user_id, $message_id);

            // get only declare credentials
            $credentials = array_only($request->json()->all(), ['read']);

            // make validator input credentials by rules
            $validator = $this->validator->make(
                $credentials,
                [
                    'read' => 'required|boolean:true'
                ]
            );

            // if not valid credentials throw exception
            if ($validator->fails()) {
                throw new BadRequestException($validator->errors());
            }

            // update message by credentials
            $message = $this->messagesProvider->updateMessage($user_id, $message_id, $credentials);

            // return updated user in json format
            return response()->json($message);

        } catch (MessageNotFoundException $e) {
            // if message does not found return message
            return $this->messageNotFound($e->getMessage());
        } catch (MessageDoesNotBelongToAUserException $e) {
            // if a message does not belong to a user return message
            return $this->messageDoesNotBelongToAUserException($e->getMessage());
        } catch (UserNotFoundException $e) {
            // if user does not found return message
            return $this->userNotFound($e->getMessage());
        } catch (BadRequestException $e) {
            // if input data does not valid return message
            return $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            // if somethings is bad return message
            return $this->internalServerError();
        }
    }


    /**
     * @param $user_id
     * @param $message_id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function destroy($user_id, $message_id)
    {
        try {
            // if input id is not numeric throw exception
            $this->checkNumericId($user_id, $message_id);

            // delete message
            $this->messagesProvider->deleteMessage($user_id, $message_id);

            // return code 204 No Content
            return response('', 204);

        } catch (MessageNotFoundException $e) {
            // if message does not found return message
            return $this->messageNotFound($e->getMessage());
        } catch (MessageDoesNotBelongToAUserException $e) {
            // if a message does not belong to a user return message
            return $this->messageDoesNotBelongToAUserException($e->getMessage());
        } catch (UserNotFoundException $e) {
            // if user does not found return message
            return $this->userNotFound($e->getMessage());
        } catch (BadRequestException $e) {
            // if input data does not valid return message
            return $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            // if somethings is bad return message
            return $this->internalServerError();
        }
    }

    /**
     * @param $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function messageNotFound($message)
    {
        return response()
            ->json([
                'code' => 404,
                'message' => $message,
            ], 404);
    }

    /**
     * @param $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function messageDoesNotBelongToAUserException($message)
    {
        return response()
            ->json([
                'code' => 403,
                'message' => $message,
            ], 403);
    }

    /**
     * @throws BadRequestException
     */
    public function checkNumericId()
    {
        $ids = func_get_args();

        foreach ($ids as $id) {
            if(!is_numeric($id)) {
                throw new BadRequestException('Passed ID '.$id.' must be a number');
            }
        }

    }
}
