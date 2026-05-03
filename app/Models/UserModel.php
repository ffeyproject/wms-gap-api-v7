<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class UserModel extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;
    protected $table = 'user';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['id', 'username', 'password_hash', 'verification_token', 'token_expired'];
    protected $hidden = ['password_hash', 'verification_token'];

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}