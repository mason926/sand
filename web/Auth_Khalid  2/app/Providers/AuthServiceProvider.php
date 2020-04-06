<?php

namespace App\Providers;

use App\Firebase\Auth\FirebaseUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('firebase', function ($app, array $config) {
            return new FirebaseUserProvider($app->make($config['model']));
        });

//        Auth::extend('firebase', function ($app, $name, array $config) {
//            return new FirebaseGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
//        });
    }
}
