<?php

namespace Database\Migrations;

use Portfolion\Database\Migration;
use Portfolion\Database\Schema\Blueprint;

class CreateUsersTable extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down(): void
    {
        $this->schema->dropIfExists('users');
    }
}