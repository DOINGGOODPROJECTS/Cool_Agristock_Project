<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function group()
	{
		return $this->belongsTo(Group::class);
	}

	public function billings()
	{
		return $this->hasMany(Billing::class, 'client_id');
	}

	public function payments()
	{
		return $this->hasMany(Payment::class, 'customer_id');
	}

    public function hispayments()
	{
		return $this->hasMany(Payment::class, 'created_by');
	}

	public function stocks()
	{
		return $this->hasMany(Stock::class, 'customer_id');
	}

    public function claims()
	{
		return $this->hasMany(Claim::class, 'customer_id');
	}

    public function histocks()
	{
		return $this->hasMany(Stock::class, 'created_by');
	}

    public function inventoryOps()
    {
        return $this->hasMany(InventoryOp::class);
    }

    public function syncSessions()
    {
        return $this->hasMany(SyncSession::class);
    }

    public function memberPhone()
    {
        return $this->hasOne(MemberPhone::class);
    }

    public function dryingBatches()
    {
        return $this->hasMany(DryingBatch::class, 'customer_id');
    }

    public function operatedDryingBatches()
    {
        return $this->hasMany(DryingBatch::class, 'operator_id');
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
