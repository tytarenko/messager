<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    return [
        'id' => '',
        'username' => $faker->name,
        'email' => $faker->email,
        'password' => str_random(8),
        'status' => array_rand_val([true, false]),
//        'created_at' => $faker->dateTime(),
//        'updates_at' => $faker->dateTime(),
    ];
});

$factory->define(App\Models\Message::class, function (Faker\Generator $faker) {
    return [
        'id' => null,
        'sender_id' => null,
        'receiver_id' => null,
        'subject' => $faker->sentences(array_rand_val([3,5,7])),
        'body' => $faker->paragraph,
        'read' => array_rand_val([true, false]),
//        'created_at' => $faker->dateTime(),
//        'updates_at' => $faker->dateTime(),
    ];
});