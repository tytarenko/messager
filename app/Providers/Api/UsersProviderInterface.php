<?php
/**
 * Created by PhpStorm.
 * User: tytar
 * Date: 17.12.15
 * Time: 15:13
 */

namespace App\Providers\Api;


interface UsersProviderInterface
{
    public function getUsers(array $params);

    public function getUser($id, array $params = []);

    public function createUser(array $credentials);

    public function updateUser($user, array $credentials);

    public function deleteUser($id);

}