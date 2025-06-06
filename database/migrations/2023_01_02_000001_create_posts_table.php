<?php

namespace Database\Migrations;

use Portfolion\Database\Migration;
use Portfolion\Database\Schema\Blueprint;

class CreatePostsTable extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down(): void
    {
        $this->schema->dropIfExists('posts');
    }
} 