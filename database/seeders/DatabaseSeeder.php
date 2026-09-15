<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        DB::table('branches')->insert([
            ['id'=>1,'name'=>'La Paz Centro','city'=>'La Paz','address'=>'Calle Sagárnaga 241','phone'=>'+591 2 244 1122','qr_token'=>Str::uuid(),'active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['id'=>2,'name'=>'Uyuni Terminal','city'=>'Uyuni','address'=>'Av. Arce 18','phone'=>'+591 2 693 2210','qr_token'=>Str::uuid(),'active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['id'=>3,'name'=>'Santa Cruz Norte','city'=>'Santa Cruz','address'=>'Av. Banzer km 3','phone'=>'+591 3 344 9080','qr_token'=>Str::uuid(),'active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('employees')->insert([
            ['branch_id'=>1,'full_name'=>'María Flores','email'=>'maria@incaland.bo','password'=>Hash::make('maria1234'),'phone'=>'+591 720 11220','role'=>'admin','active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['branch_id'=>1,'full_name'=>'Daniel Choque','email'=>'daniel@incaland.bo','password'=>Hash::make('daniel1234'),'phone'=>'+591 701 88430','role'=>'receptionist','active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['branch_id'=>2,'full_name'=>'Camila Quispe','email'=>'camila@incaland.bo','password'=>Hash::make('camila1234'),'phone'=>'+591 712 33421','role'=>'receptionist','active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('tour_categories')->insert([
            ['id'=>1,'name'=>'Naturaleza','color'=>'#F0B90B','created_at'=>$now,'updated_at'=>$now],
            ['id'=>2,'name'=>'Aventura','color'=>'#E00022','created_at'=>$now,'updated_at'=>$now],
            ['id'=>3,'name'=>'Cultura','color'=>'#1D7A52','created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('tour_packages')->insert([
            ['id'=>1,'tour_category_id'=>1,'name'=>'Salar de Uyuni','location'=>'Potosí','duration_days'=>3,'price'=>1450,'description'=>'Salar, lagunas de colores y desierto de Siloli.','image_path'=>'/images/bolivia-hero.png','active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['id'=>2,'tour_category_id'=>2,'name'=>'Nevado Sajama','location'=>'Oruro','duration_days'=>2,'price'=>980,'description'=>'Ascenso andino, termas y paisaje volcánico.','image_path'=>'/images/bolivia-hero.png','active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['id'=>3,'tour_category_id'=>3,'name'=>'Tiwanaku Esencial','location'=>'La Paz','duration_days'=>1,'price'=>320,'description'=>'Historia viva del altiplano boliviano.','image_path'=>'/images/bolivia-hero.png','active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ]);
        $homeSlides = [
            ['title'=>'El cielo también viaja contigo.','subtitle'=>'Cruza el espejo infinito del Salar de Uyuni en una expedición diseñada para recordar toda la vida.','image_path'=>'/images/carousel-uyuni-sunrise.png','display_order'=>1,'active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['title'=>'Donde la aventura toca el cielo.','subtitle'=>'Camina entre volcanes, lagunas y horizontes intactos en el corazón del Parque Nacional Sajama.','image_path'=>'/images/carousel-sajama.png','display_order'=>2,'active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['title'=>'Historias talladas en piedra.','subtitle'=>'Descubre Tiwanaku con guías locales que convierten cada vestigio en una historia viva.','image_path'=>'/images/carousel-tiwanaku.png','display_order'=>3,'active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ];
        foreach ($homeSlides as $homeSlide) {
            DB::table('home_slides')->updateOrInsert(['image_path'=>$homeSlide['image_path']], $homeSlide);
        }
        DB::table('equipment')->insert([
            ['id'=>1,'name'=>'Bicicleta Trek Marlin','category'=>'Ciclismo','size'=>'M / L','stock'=>12,'available_stock'=>8,'condition'=>'Excelente','created_at'=>$now,'updated_at'=>$now],
            ['id'=>2,'name'=>'Traje de escalada','category'=>'Alta montaña','size'=>'S / M / L / XL','stock'=>18,'available_stock'=>14,'condition'=>'Excelente','created_at'=>$now,'updated_at'=>$now],
            ['id'=>3,'name'=>'Casco Petzl','category'=>'Seguridad','size'=>'Ajustable','stock'=>24,'available_stock'=>19,'condition'=>'Bueno','created_at'=>$now,'updated_at'=>$now],
            ['id'=>4,'name'=>'Botas impermeables','category'=>'Trekking','size'=>'36–45','stock'=>20,'available_stock'=>11,'condition'=>'Excelente','created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('lodgings')->insert([
            ['name'=>'Casa Andina La Paz','city'=>'La Paz','address'=>'Calle Linares 880','rooms'=>18,'available_rooms'=>7,'active'=>true,'created_at'=>$now,'updated_at'=>$now],
            ['name'=>'Refugio Blanco Uyuni','city'=>'Uyuni','address'=>'Av. Ferroviaria 40','rooms'=>12,'available_rooms'=>4,'active'=>true,'created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('tourists')->insert([
            ['id'=>1,'full_name'=>'Sophie Martin','nationality'=>'Francia','passport_number'=>'FR84A2201','food_notes'=>'Vegetariana','apparel_size'=>'M','height_cm'=>168,'hotel'=>'Casa Andina','whatsapp'=>'+33 612 884 201','preferred_language'=>'fr','created_at'=>$now,'updated_at'=>$now],
            ['id'=>2,'full_name'=>'Lucas Ferreira','nationality'=>'Brasil','passport_number'=>'BR992810','food_notes'=>'Sin maní','apparel_size'=>'L','height_cm'=>181,'hotel'=>'Luna Salada','whatsapp'=>'+55 11 98830 4412','preferred_language'=>'pt','created_at'=>$now,'updated_at'=>$now],
            ['id'=>3,'full_name'=>'Emma Wilson','nationality'=>'Canadá','passport_number'=>'CA300129','food_notes'=>'Sin restricciones','apparel_size'=>'S','height_cm'=>163,'hotel'=>'Atix Hotel','whatsapp'=>'+1 416 555 0142','preferred_language'=>'en','created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('departures')->insert([
            ['id'=>1,'tour_package_id'=>1,'tour_date'=>now()->addDays(2)->toDateString(),'includes'=>'Transporte, alimentación y hospedaje','excludes'=>'Bebidas y propinas','guide'=>'Julio Mamani','driver'=>'Óscar Rojas','status'=>'open','created_at'=>$now,'updated_at'=>$now],
            ['id'=>2,'tour_package_id'=>2,'tour_date'=>now()->addDays(2)->toDateString(),'includes'=>null,'excludes'=>null,'guide'=>null,'driver'=>null,'status'=>'open','created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('reservations')->insert([
            ['id'=>1,'tourist_id'=>1,'branch_id'=>1,'tour_package_id'=>null,'departure_id'=>null,'source'=>'qr','status'=>'pending_package','tour_date'=>null,'created_at'=>$now,'updated_at'=>$now],
            ['id'=>2,'tourist_id'=>2,'branch_id'=>2,'tour_package_id'=>1,'departure_id'=>1,'source'=>'online','status'=>'pending_equipment','tour_date'=>now()->addDays(2)->toDateString(),'created_at'=>$now,'updated_at'=>$now],
            ['id'=>3,'tourist_id'=>3,'branch_id'=>1,'tour_package_id'=>1,'departure_id'=>1,'source'=>'qr','status'=>'confirmed','tour_date'=>now()->addDays(2)->toDateString(),'created_at'=>$now,'updated_at'=>$now],
        ]);
        DB::table('equipment_reservation')->insert([
            ['reservation_id'=>3,'equipment_id'=>3,'quantity'=>1,'assigned_size'=>'Ajustable','created_at'=>$now,'updated_at'=>$now],
            ['reservation_id'=>3,'equipment_id'=>4,'quantity'=>1,'assigned_size'=>'38','created_at'=>$now,'updated_at'=>$now],
        ]);
    }
}
