<?php namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * @var int
     */
    protected $defaultLimit = 10;
    /**
     * @var int
     */
    protected $defaultOffset = 0;
    /**
     * @var null
     */
    protected $defaultSort = null;
    /**
     * @var array
     */
    protected $defaultFields = ['*'];
    /**
     * @var bool
     */
    protected $defaultStatus = true;
    /**
     * @var string
     */
    protected $defaultType = 'all';

    /**
     * @var array
     */
    protected $availableFields = [];
    /**
     * @var int
     */
    protected $maxLimit = 100;
    /**
     * @var array
     */
    protected $sortFlags = ['asc', 'desc'];
    /**
     * @var array
     */
    protected $types = [];



    /**
     * @param $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function userNotFound($message)
    {
        return response()
            ->json([
                'code' => 404,
                'message' => $message
            ], 404);
    }


    /**
     * @param $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function badRequest($message)
    {
        return response()
            ->json([
                'code' => 400,
                'message' => $message,
            ], 400);
    }



    /**
     * @return \Illuminate\Http\JsonResponse
     */
    protected function internalServerError()
    {
        return response()
            ->json([
                'code' => 500,
                'message' => 'Internal server error'
            ], 500);
    }


    /**
     * @param Request $request
     * @param array $params
     * @return array
     */
    public function parseOptions(
        Request $request,
        array $params
    ) {
        $parsed = [];
        foreach ($params as $name => $value) {
            $raw = $request->input($name, null);
            if(is_null($raw) || strlen($raw) === 0) {
                $parsed[$name] = $value;
            } else {
                $parser = 'parse' . ucfirst($name);
                if(method_exists($this, $parser))
                    $parsed[$name] = $this->{$parser}($raw);
            }
        }
        return $parsed;
    }

    /**
     * @param $raw_string
     * @return array
     */
    public function parseFields($raw_string)
    {
        $parsed = [];
        $filter_string = preg_replace('/,+/', ',', $raw_string);
        $fields = explode(',', $filter_string);
        $fields = array_map('trim', $fields);

        foreach ($fields as $field) {
            if(!in_array($field, $this->availableFields))
                continue;
            $parsed[] = $field;
        }
        if( empty($parsed))
            $parsed = null;

        return $parsed;
    }


    /**
     * @param $raw_string
     * @return array
     */
    public function parseSort($raw_string)
    {
        $parsed = [];
        if(strlen($raw_string) > 0) {
            $filter_string = preg_replace('/,+/', ',', $raw_string);
            $rows = explode(',', $filter_string);
            $rows = array_map('trim', $rows);

            foreach ($rows as $row) {
                list($filed, $flag) = explode(':', $row);
                if(in_array($filed, $this->availableFields) && in_array($flag, $this->sortFlags)){
                    $parsed[$filed] = $flag;
                }
            }
        }
        if( empty($parsed))
            $parsed = null;

        return $parsed;
    }

    /**
     * @param $raw_string
     * @return int
     */
    public function parseLimit($raw_string)
    {
        if(is_numeric($raw_string)) {
            $limit = intval($raw_string);
            if (0 < $limit and $limit <= $this->maxLimit)
                return  $limit;
            else
                return $this->defaultLimit;
        }
        return $this->defaultLimit;
    }

    /**
     * @param $raw_string
     * @return int
     */
    public function parseOffset($raw_string)
    {
        if(is_numeric($raw_string)){
            $limit = intval($raw_string);
            if($limit >= 0)
                return $limit;
            else
                return $this->defaultOffset;
        }
        return $this->defaultOffset;
    }

    /**
     * @param $status
     * @return bool
     */
    public function parseStatus($status)
    {
        if(in_array($status, ['unread', 'read']))
            if($status === 'unread')
                return false;
            else
                return true;
        return $this->defaultStatus;
    }

    /**
     * @param $type
     * @return string
     */
    public function parseType($type)
    {
        if(in_array($type, $this->types))
            return $type;
        else
            return $this->defaultType;
    }
}
