<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

class CreateRoutingTable extends Migration
{
    protected $schema;

    /**
     * Create a new migration instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->schema = (new Schema)->connection($this->getConnection())->getSchemaBuilder();
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->schema->create('services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index()->unique();
            $table->string('host');
            $table->boolean('enabled')->default(false);
            $table->boolean('default')->default(false);
            $table->string('prefix', 50)->nullable()->default('api');
            $table->string('version', 5)->nullable();
            $table->string('description')->nullable();
            $table->integer('limit')->default(10);
            $table->integer('weight')->default(1);
            $table->string('health_check_path')->nullable();
            $table->boolean('down')->default(false);
            $table->integer('hit')->default(0);
            $table->timestamps();
        });

        $this->schema->create('groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('description')->nullable();
        });

        $this->schema->create('routes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('path');
            $table->string('description')->nullable();
            $table->string('environment')->nullable()->comment('environment api service exp prod, dev, staging');
            $table->text('parameters')->nullable()->comment('to define the requirements of the parameters');

            $table->string('type')->default('http')->comment('http, echo, mock');
            $table->string('method')->nullable()->comment('GET,POST,PUT,DELETE,PATCH');
            $table->string('format')->default('json'); // format return raw or json
            
            $table->text('headers')->nullable();
            $table->text('body')->nullable();

            $table->string('action')->nullable();

            $table->boolean('public')->default(false); // auth or check
            $table->integer('limit')->default(-1); // limit hit per minutes set -1 unlimited hit
            $table->text('middleware')->nullable();

            $table->tinyInteger('priority')->default(1);
            $table->tinyInteger('timeout')->default(30); // request timeout in seconds
            $table->boolean('active')->default(false);
            $table->boolean('deprecated')->default(false);
        
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            // $table->foreign('group_id')->references('id')->on('groups');
        });

        $this->schema->create('route_services', function (Blueprint $table) {
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('route_id');

            $table->foreign('service_id')->references('id')->on('services');
            $table->foreign('route_id')->references('id')->on('routes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->schema->dropIfExists('route_services');
        $this->schema->dropIfExists('routes');
        $this->schema->dropIfExists('services');
        $this->schema->dropIfExists('groups');
        
    }

    /**
     * Get the migration connection name.
     *
     * @return string|null
     */
    public function getConnection()
    {
        return 'default';
    }
}
