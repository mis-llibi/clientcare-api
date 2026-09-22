<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * The database connection that should be used by the migration.
     */
    protected $connection = 'portal_request_db';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::hasTable('hati_api_logs')){
            Schema::create('hati_api_logs', function(Blueprint $table){
                $table->id();
                $table->string('ip_address');
                $table->string('link_parameter');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hati_api_logs');
    }
};
