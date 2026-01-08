<?php
namespace Kuroragi\GeneralHelper\Macros;

use Illuminate\Database\Schema\Blueprint;

class BlueprintMacros
{
    public static function register()
    {
        /**
         * Add blameable columns (created_by, updated_by, deleted_by)
         * 
         * @param string|null $userTable The users table name for foreign key constraint
         * @param bool $foreignKey Whether to add foreign key constraints
         * @return void
         */
        Blueprint::macro('blameable', function (?string $userTable = 'users', bool $foreignKey = true) {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('created_by')->nullable();
            $this->unsignedBigInteger('updated_by')->nullable();
            $this->unsignedBigInteger('deleted_by')->nullable();

            if ($foreignKey && $userTable) {
                $this->foreign('created_by')->references('id')->on($userTable)->nullOnDelete();
                $this->foreign('updated_by')->references('id')->on($userTable)->nullOnDelete();
                $this->foreign('deleted_by')->references('id')->on($userTable)->nullOnDelete();
            }
        });

        /**
         * Add only created_by column
         * 
         * @param string|null $userTable The users table name for foreign key constraint
         * @param bool $foreignKey Whether to add foreign key constraint
         * @return void
         */
        Blueprint::macro('createdBy', function (?string $userTable = 'users', bool $foreignKey = true) {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('created_by')->nullable();

            if ($foreignKey && $userTable) {
                $this->foreign('created_by')->references('id')->on($userTable)->nullOnDelete();
            }
        });

        /**
         * Add only updated_by column
         * 
         * @param string|null $userTable The users table name for foreign key constraint
         * @param bool $foreignKey Whether to add foreign key constraint
         * @return void
         */
        Blueprint::macro('updatedBy', function (?string $userTable = 'users', bool $foreignKey = true) {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('updated_by')->nullable();

            if ($foreignKey && $userTable) {
                $this->foreign('updated_by')->references('id')->on($userTable)->nullOnDelete();
            }
        });

        /**
         * Add only deleted_by column
         * 
         * @param string|null $userTable The users table name for foreign key constraint
         * @param bool $foreignKey Whether to add foreign key constraint
         * @return void
         */
        Blueprint::macro('deletedBy', function (?string $userTable = 'users', bool $foreignKey = true) {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('deleted_by')->nullable();

            if ($foreignKey && $userTable) {
                $this->foreign('deleted_by')->references('id')->on($userTable)->nullOnDelete();
            }
        });

        /**
         * Drop blameable columns (created_by, updated_by, deleted_by)
         * 
         * @param string|null $tableName Current table name (for dropping foreign keys)
         * @param bool $foreignKey Whether to drop foreign key constraints first
         * @return void
         */
        Blueprint::macro('dropBlameable', function (?string $tableName = null, bool $foreignKey = true) {
            /** @var Blueprint $this */
            if ($foreignKey && $tableName) {
                $this->dropForeign(["{$tableName}_created_by_foreign"]);
                $this->dropForeign(["{$tableName}_updated_by_foreign"]);
                $this->dropForeign(["{$tableName}_deleted_by_foreign"]);
            }
            
            $this->dropColumn(['created_by', 'updated_by', 'deleted_by']);
        });
    }
}
