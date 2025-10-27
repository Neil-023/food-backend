<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    use Notifiable;

    use HasFactory;

    protected $table = 'users_tbl'; // Specify the table name

    // If the primary key is not 'id', define it here
    protected $primaryKey = 'user_id';
    public $incrementing = true;
    protected $keyType = 'int';
    // If the table doesn't have timestamps, set to false
    public $timestamps = true;

    // Define the fillable attributes (you can also use guarded)
    protected $fillable = [
        'username', 'password', 'full_name', 'address', 'contact_number', 
        'email_address', 'role', 'shop_name', 'shop_tagline', 'image',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
      // if no image, return null or a default
      if (! $this->image) return null;
      // asset('storage/...') will point to public/storage/...
      return asset('storage/' . $this->image);
    }
}
