<?php namespace App\Http\Controllers\Api;

use App\Exceptions\Api\BadRequestException;
use App\Providers\Api\UsersProviderInterface;
use Illuminate\Http\Request;
use \Illuminate\Validation\Factory as Validator;
use App\Http\Requests;

use App\Exceptions\Api\UserNotFoundException as UserNotFoundException;

class UsersController extends ApiController
{
    /**
     * @var array
     */
    protected $availableFields = ['id', 'username', 'email', 'status', 'created_at'];
    /**
     * @var array
     */
    protected $types = ['all', 'online', 'offline'];
    /**
     * @var string
     */
    protected $defaultType = 'all';

    /**
     * @var UsersProviderInterface
     */
    protected $usersProvider;
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * UsersController constructor.
     *
     * @param UsersProviderInterface $usersProvider
     * @param Validator $validator
     */
    public function __construct(
        UsersProviderInterface $usersProvider,
        Validator $validator
    )
    {
        $this->usersProvider = $usersProvider;
        $this->validator = $validator;
    }


    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Set default options
        $defaultOptions = [
            'limit' => $this->defaultLimit,
            'offset' => $this->defaultOffset,
            'sort' => $this->defaultSort,
            'fields' => $this->defaultFields,
            'type' => $this->defaultType // all online offline
        ];
        try {
            // parse input raw options
            $parsedOptions = $this->parseOptions($request, $defaultOptions);

            // get collection of users by options
            $users = $this->usersProvider->getUsers($parsedOptions);

            // return collection in json format
            return response()->json($users);
        } catch (\Exception $e) {
            // if somethings is bad return message
            return $this->internalServerError();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // get only declare credentials
            $credentials = array_only($request->json()->all(), ['username', 'email', 'password']);

            // validate input credentials by rules
            $validator = $this->validator->make(
                $credentials,
                [
                    'username' => 'required|max:255',
                    'email' => 'required|email',
                    'password' => 'required|min:6|max:60'
                ]
            );

            // if not valid credentials throw exception
            if ($validator->fails()) {
                throw new BadRequestException($validator->errors());
            }

            // create user by credentials
            $user = $this->usersProvider->createUser($credentials);

            // return created user in json format
            return response()->json($user, 201);

        } catch (BadRequestException $e) {
            // if input credentials not valid return message
            return $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            // if somethings is bad return message
            return $this->internalServerError();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            // if input id is not numeric throw exception
            $this->checkNumericId($id);

            // set default options
            $defaultOptions = [
                'fields' => $this->defaultFields,
            ];

            // parse input raw options
            $parsedOptions = $this->parseOptions($request, $defaultOptions);

            // get user by id and options
            $user = $this->usersProvider->getUser($id, $parsedOptions);

            // return option in json format
            return response()->json($user);

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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // if input id is not numeric throw exception
            $this->checkNumericId($id);

            // get only declare credentials
            $credentials = array_only($request->json()->all(), ['username', 'email', 'password', 'status']);

            // make validator by method input credentials by rules
            if($request->isMethod('put'))
                $validator = $this->putValidator($credentials);
            else
                $validator = $this->patchValidator($credentials);

            // if not valid credentials throw exception
            if ($validator->fails()) {
                throw new BadRequestException($validator->errors());
            }

            try {
                // update user by credentials
                $user = $this->usersProvider->updateUser($id, $credentials);
                // return updated user in json format
                return response()->json($user);
            } catch (UserNotFoundException $e) {
                // if http method is put
                if($request->isMethod('put')) {
                    // create new user by credential
                    $user = $this->usersProvider->createUser($credentials);
                    // return updated user in json format
                    return response()->json($user, 201);
                } else {
                    // if method is patch throw exception
                    throw new UserNotFoundException($e->getMessage());
                }
            }
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
     * @param $credentials
     * @return \Illuminate\Validation\Validator
     */
    protected function putValidator($credentials)
    {
        return $this->validator->make(
            $credentials,
            [
                'username' => 'required|max:255',
                'email' => 'required|email',
                'password' => 'required|min:6|max:60',
                'status' => 'boolean'
            ]
        );
    }

    /**
     * @param $credentials
     * @return \Illuminate\Validation\Validator
     */
    protected function patchValidator($credentials)
    {
        return $this->validator->make(
            $credentials,
            [
                'username' => 'max:255',
                'email' => 'email',
                'password' => 'min:6|max:60',
                'status' => 'boolean'
            ]
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // if input id is not numeric throw exception
            $this->checkNumericId($id);

            // delete user
            $this->usersProvider->deleteUser($id);

            // return code 204 No Content
            return response('', 204);

        } catch (UserNotFoundException $e) {
            // if user does not found return message
            return $this->userNotFound($id);
        } catch (BadRequestException $e) {
            // if input data does not valid return message
            return $this->badRequest($e->getMessage());
        } catch (\Exception $e) {
            // if somethings is bad return message
            return $this->internalServerError();
        }
    }

    /**
     * @param $id
     * @throws BadRequestException
     */
    public function checkNumericId($id)
    {
        if(!is_numeric($id)) {
            throw new BadRequestException('Passed ID '.$id.' must be a number');
        }
    }
}
