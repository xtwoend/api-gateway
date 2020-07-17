<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->string('description')->nullable();
            $table->string('environment')->nullable()->comment('environment api service exp prod, dev, staging');
            $table->tinyInteger('version')->nullable();

            $table->string('type')->default('http')->comment('http, echo, mock');
            $table->string('method')->nullable()->comment('GET,POST,PUT,DELETE,PATCH');
            $table->string('format')->nullable();
            $table->text('headers')->nullable();
            $table->string('content')->nullable();
            $table->boolean('public')->default(false);

            $table->tinyInteger('retry')->default(2);
            $table->tinyInteger('retry_delay')->default(30);
            $table->boolean('active')->default(FALSE);

            $table->foreignId('group_id');
            $table->foreignId('user_id');

            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups');
        Schema::dropIfExists('routes');
    }
}
