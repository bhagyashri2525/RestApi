<?php

namespace App\Services\Management;

use App\Models\User;

class UserManagementService
{
    public function userList()
    {
        return User::all();
    }

}
