<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    protected $table = 'user';
    protected $primaryKey = 'id';
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    // protected $fillable = [
    //     'name', 'email',
    // ];
    protected $fillable = ['id', 'username', 'password_hash', 'verification_token', 'token_expired'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    // protected $hidden = [
    //     'password',
    // ];
    protected $hidden = ['password_hash', 'verification_token'];
}
