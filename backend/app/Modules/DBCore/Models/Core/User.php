<?php

namespace Modules\DBCore\Models\Core;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Modules\DBCore\Policies\UserPolicy;
use Modules\SchemaMgr\Support\UsesSchema;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * User Model
 *
 * Represents users in the FetchIt application.
 * Users are stored in the 'fetchit' schema.
 *
 * @package Modules\DBCore\Models\Core
 * @version 1.0.0
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property string|null $google_id
 * @property text|null $picture
 * @property string|null $locale
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_login
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, UsesSchema, SoftDeletes;

    /**
     * The schema this model belongs to
     * Set from configuration, defaults to 'fetchit'
     * 
     * @var string|null
     */
    protected $schema;

    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'status',
        'last_login',
        'google_id',
        'picture',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
            'last_login' => 'datetime',
        ];
    }

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set schema from configuration if not already set
        if (!isset($this->schema)) {
            $this->schema = config('dbcore.fetchit_schema', 'fetchit');
        }
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set schema from configuration for all instances
        static::retrieved(function ($model) {
            if (!isset($model->schema)) {
                $model->schema = config('dbcore.fetchit_schema', 'fetchit');
            }
        });

        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }

            // Ensure schema is set from config
            if (!isset($user->schema)) {
                $user->schema = config('dbcore.fetchit_schema', 'fetchit');
            }
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\DBCore\Database\Factories\Core\UserFactory::new();
    }

    /**
     * Create a new user with password
     *
     * @param string $email
     * @param string $hashedPassword
     * @param string $name
     * @param string|null $firstName
     * @param string|null $lastName
     * @return self
     */
    public static function createWithPassword(
        string $email,
        string $hashedPassword,
        string $name,
        ?string $firstName = null,
        ?string $lastName = null
    ): self {
        return static::create([
            'email' => $email,
            'password' => $hashedPassword,
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
        ]);
    }

    /**
     * Update user password
     *
     * @param string $hashedPassword
     * @return bool
     */
    public function updatePassword(string $hashedPassword): bool
    {
        return $this->update(['password' => $hashedPassword]);
    }

    /**
     * Scope: Filter active users
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->uuid;
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
