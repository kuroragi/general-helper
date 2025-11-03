<?php
namespace Kuroragi\GeneralHelper\Traits;

use Illuminate\Support\Facades\Auth;

trait Blameable
{
    public static function bootBlameable()
    {
        static::creating(function ($model) {
            $userId = self::currentAuthId();
            if ($userId && $model->isFillable('created_by')) {
                $model->created_by = $userId;
            }
        });

        static::updating(function ($model) {
            $userId = self::currentAuthId();
            if ($userId && $model->isFillable('updated_by')) {
                $model->updated_by = $userId;
            }
        });

        static::deleting(function ($model) {
            $userId = self::currentAuthId();
            if ($userId && $model->isFillable('deleted_by')) {
                // soft delete: set deleted_by and save
                if (method_exists($model, 'runSoftDelete')) {
                    $model->deleted_by = $userId;
                    $model->save();
                }
            }
        });
    }

    public function createdBy()
    {
        return $this->belongsTo($this->authModel(), 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo($this->authModel(), 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo($this->authModel(), 'deleted_by');
    }

    protected static function currentAuthId()
    {
        if (config('kuroragi.auth_model')) {
            // still use Auth facade to get id, but allow custom guard later if needed
        }
        return Auth::id();
    }

    protected function authModel()
    {
        return config('kuroragi.auth_model') ?: config('auth.providers.users.model');
    }
}
