<?php

namespace App\Providers;

use App\Models\UserModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
            $token = $request->input('api_token') ?? $request->input('verification_token');
            if ($token) {
                return UserModel::where('verification_token', $token)->first();
            }

            $headerToken = $request->header('Authorization');
            if ($headerToken) {
                $cleanToken = str_replace('Bearer ', '', $headerToken);
                return UserModel::where('verification_token', $cleanToken)->first();
            }

            return null;
        });
    }
}
