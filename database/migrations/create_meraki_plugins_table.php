<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('meraki_plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('status', 20)->default('active');
            $table->json('meta')->nullable();
            $table->boolean('enabled')->default(false);
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->string('version')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meraki_plugins');
    }

};
