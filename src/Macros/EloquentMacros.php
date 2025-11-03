<?php
namespace Kuroragi\GeneralHelper\Macros;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EloquentMacros
{
    public static function register()
    {
        Builder::macro('createdBy', function ($userId = null) {
            /** @var Builder $this */
            $userId = $userId ?: Auth::id();
            return $this->where($this->getModel()->getTable().'.created_by', $userId);
        });

        Builder::macro('updatedBy', function ($userId = null) {
            $userId = $userId ?: Auth::id();
            return $this->where($this->getModel()->getTable().'.updated_by', $userId);
        });

        Builder::macro('deletedBy', function ($userId = null) {
            $userId = $userId ?: Auth::id();
            return $this->where($this->getModel()->getTable().'.deleted_by', $userId);
        });
    }
}
