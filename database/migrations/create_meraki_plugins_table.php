<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('meraki_plugins', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->boolean('enabled')->default(false);
            $table->string('version')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meraki_plugins');
    }

};
