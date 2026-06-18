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
            $table->string('version', 20)->default('');
            $table->string('status', 20)->default('active');
            $table->timestamp('installed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meraki_plugins');
    }

};
