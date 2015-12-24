<?php

namespace App\Providers;


use App\Providers\Api\MessagesProviderInterface;
use App\Providers\Api\UsersProvider;
use App\Providers\Api\MessagesProvider;
use App\Providers\Api\UsersProviderInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UsersProviderInterface::class, UsersProvider::class);
        $this->app->bind(MessagesProviderInterface::class, MessagesProvider::class);
    }
}
