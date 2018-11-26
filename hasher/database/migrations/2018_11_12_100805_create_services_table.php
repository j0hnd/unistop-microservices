<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('application_id')->nullable();
            $table->string('name', 50);
            $table->string('slug', 100);
            $table->string('salt', 100)->nullable();
            $table->tinyInteger('enabled')->default(1);
			$table->integer('created_by');
			$table->integer('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['application_id', 'name', 'enabled']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('services');
    }
}
