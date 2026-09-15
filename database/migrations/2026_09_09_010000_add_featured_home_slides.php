<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $slides = [
            [
                'title' => 'El cielo también viaja contigo.',
                'subtitle' => 'Cruza el espejo infinito del Salar de Uyuni en una expedición diseñada para recordar toda la vida.',
                'image_path' => '/images/carousel-uyuni-sunrise.png',
                'display_order' => 1,
            ],
            [
                'title' => 'Donde la aventura toca el cielo.',
                'subtitle' => 'Camina entre volcanes, lagunas y horizontes intactos en el corazón del Parque Nacional Sajama.',
                'image_path' => '/images/carousel-sajama.png',
                'display_order' => 2,
            ],
            [
                'title' => 'Historias talladas en piedra.',
                'subtitle' => 'Descubre Tiwanaku con guías locales que convierten cada vestigio en una historia viva.',
                'image_path' => '/images/carousel-tiwanaku.png',
                'display_order' => 3,
            ],
        ];

        foreach ($slides as $slide) {
            DB::table('home_slides')->updateOrInsert(
                ['image_path' => $slide['image_path']],
                $slide + ['active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }

        DB::table('home_slides')
            ->where('image_path', '/images/bolivia-hero.png')
            ->update(['active' => false, 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('home_slides')->whereIn('image_path', [
            '/images/carousel-uyuni-sunrise.png',
            '/images/carousel-sajama.png',
            '/images/carousel-tiwanaku.png',
        ])->delete();

        DB::table('home_slides')
            ->where('image_path', '/images/bolivia-hero.png')
            ->update(['active' => true, 'updated_at' => now()]);
    }
};
