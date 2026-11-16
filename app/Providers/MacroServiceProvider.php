<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * UUID foreign key shortcut
         */
        Blueprint::macro('uuidForeign', function (string $column) {
            return $this->uuid($column);
        });

        Blueprint::macro('constrainedUuid', function (
            string $column,
            string $table,
            string $foreignColumn = 'id',
            string $onDelete = 'cascade'
        ) {
            $this->uuid($column);

            $this->foreign($column)
                ->references($foreignColumn)
                ->on($table)
                ->{"{$onDelete}OnDelete"}();

            return $this;
        });
    }
}