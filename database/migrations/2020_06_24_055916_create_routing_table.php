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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('host');
            $table->boolean('enabled')->default(false);
            $table->string('prefix', 50)->nullable()->default('api');
            $table->string('description')->nullable();
            $table->integer('limit')->default(10);
            $table->integer('weight')->default(1);
            $table->string('health_check_path')->nullable();
            $table->boolean('down')->default(false);
            $table->integer('hit')->default(0);
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('description')->nullable();
            $table->string('environment')->nullable()->comment('environment api service exp prod, dev, staging');

            $table->string('type')->default('http')->comment('http, echo, mock');
            $table->string('method')->nullable()->comment('GET,POST,PUT,DELETE,PATCH');
            $table->string('format')->default('json'); // format return raw or json
            
            $table->text('headers')->nullable();
            $table->text('body')->nullable();

            $table->string('action')->nullable();

            $table->boolean('public')->default(false); // auth or check
            $table->integer('cache_lifetime')->default(-1); // cache request hit set -1 not cache
            $table->integer('limit')->default(-1); // limit hit per minutes set -1 unlimited hit
            $table->text('middleware')->nullable();

            $table->tinyInteger('priority')->default(1);
            $table->tinyInteger('timeout')->default(30); // request timeout in seconds
            $table->boolean('active')->default(FALSE);
            
            $table->foreignId('group_id');
            $table->foreignId('user_id');

            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create('route_services', function (Blueprint $table) {
            $table->foreignId('service_id')->constrained('services');
            $table->foreignId('route_id')->constrained('routes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
        
        
        Schema::dropIfExists('route_services');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('services');
        Schema::dropIfExists('groups');
        
    }
}
