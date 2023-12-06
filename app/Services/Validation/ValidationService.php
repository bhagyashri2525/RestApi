<?php

namespace App\Services\Validation;

use App\Models\User;

class ValidationService
{
    const EMAIL_ALREADY_REGISTERED = 'already_exist';

    const MESSSAGES = [
        self::EMAIL_ALREADY_REGISTERED => 'email address already registered.',
    ];

    public function isEmailExist($email)
    {
        if (User::where(['email' => $email])->count()) {
            return true;
        }
        return false;
    }

    public function isEmail($email) {
        if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        return false;
   }

}
