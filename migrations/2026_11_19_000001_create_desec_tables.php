<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desec_domains', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 190)->unique();
            $table->text('token')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('desec_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('server_id')->index();
            $table->unsignedBigInteger('domain_id')->index();
            $table->string('subdomain', 120)->default('');
            $table->string('target', 255);
            $table->unsignedSmallInteger('port');
            $table->string('address', 45)->nullable();
            $table->text('records');
            $table->text('notes')->nullable();
            $table->string('status', 16)->default('active');
            $table->string('last_error', 500)->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['domain_id', 'subdomain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desec_allocations');
        Schema::dropIfExists('desec_domains');
    }
};
