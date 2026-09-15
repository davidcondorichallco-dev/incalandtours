<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title', 140);
            $table->string('subtitle', 280)->nullable();
            $table->string('image_path');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('home_slides')->insert([
            'title' => 'Paisajes que no caben en una foto.',
            'subtitle' => 'Explora Bolivia con un equipo local que cuida cada detalle de tu viaje.',
            'image_path' => '/images/bolivia-hero.png',
            'display_order' => 1,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('home_slides');
    }
};
