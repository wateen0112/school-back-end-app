<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform security policies (password rules, session timeouts, 2FA...).
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('security_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('code', 100)->unique();
            $table->text('description')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('security_policies');
    }
};