<?php
/**
 * Created by PhpStorm.
 * User: tytar
 * Date: 21.12.15
 * Time: 14:22
 */

namespace App\Providers\Api;


class ApiProvider
{
    /**
     * @var
     */
    protected $fields;
    /**
     * @var
     */
    protected $sort;
    /**
     * @var
     */
    protected $offset;
    /**
     * @var
     */
    protected $limit;
    /**
     * @var
     */
    protected $type;
    /**
     * @var
     */
    protected $status;

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        if(isset($options['type']) && !empty($options['type']))
            $this->type = $options['type'];

        if(isset($options['sort'])
            && is_array($options['sort'])
            && !empty($options['sort'])
        )
            $this->sort = $options['sort'];

        if(isset($options['offset']) && !empty($options['offset']))
            $this->offset = $options['offset'];

        if(isset($options['limit']) && !empty($options['limit']))
            $this->limit = $options['limit'];

        if(isset($options['fields'])
            && is_array($options['fields'])
            && !empty($options['fields'])
        )
            $this->fields = $options['fields'];

        if(isset($options['type']) && !empty($options['type']))
            $this->type = $options['type'];

        if(isset($options['status']) && !empty($options['status']))
            $this->status = $options['status'];
    }

}