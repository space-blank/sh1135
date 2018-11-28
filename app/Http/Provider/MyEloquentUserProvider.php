<?php

namespace App\Http\Provider;


use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class MyEloquentUserProvider extends EloquentUserProvider
{
    /**
     * @param UserContract $user
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(UserContract $user, array $credentials)
    {
        $plain = $credentials['password'];
        $authPassword = $user->getAuthPassword();

        return md5($plain) == $authPassword;
    }

}