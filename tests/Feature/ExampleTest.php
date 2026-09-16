<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_is_visible_without_login_and_panel_requires_staff(): void
    {
        $this->withoutExceptionHandling();
        $this->seed(DatabaseSeeder::class);
        $this->get('/')->assertOk()->assertSee('Experiencias en Bolivia', false);
        $this->get('/panel')->assertRedirect('/ingresar');
        $this->withSession(['staff_id'=>1,'staff_role'=>'admin','staff_branch_id'=>1])->get('/panel')->assertOk()->assertSee('CENTRO DE OPERACIONES', false);
    }

    public function test_admin_can_manage_the_home_carousel(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $session = ['staff_id'=>1,'staff_role'=>'admin','staff_branch_id'=>1];

        $created = $this->withSession($session)->post('/catalogo/slides', [
            'title'=>'Aventura en el altiplano',
            'subtitle'=>'Un viaje entre montañas, lagunas y cultura viva.',
            'display_order'=>2,
            'active'=>1,
            'image'=>new UploadedFile(public_path('images/bolivia-hero.png'), 'altiplano.png', 'image/png', null, true),
        ]);

        $created->assertOk()->assertJsonPath('ok', true);
        $slideId = $created->json('id');
        $this->assertDatabaseHas('home_slides', ['id'=>$slideId, 'title'=>'Aventura en el altiplano']);
        $this->get('/')->assertOk()->assertSee('Aventura en el altiplano', false);

        $this->withSession($session)->putJson('/catalogo/slides/'.$slideId, [
            'title'=>'Altiplano inolvidable',
            'subtitle'=>'Una nueva descripción.',
            'display_order'=>3,
            'active'=>0,
        ])->assertOk();
        $this->assertDatabaseHas('home_slides', ['id'=>$slideId, 'title'=>'Altiplano inolvidable', 'active'=>0]);

        $this->withSession($session)->deleteJson('/carrusel/'.$slideId)->assertOk();
        $this->assertDatabaseMissing('home_slides', ['id'=>$slideId]);
    }

    public function test_online_booking_creates_a_departure_and_pending_equipment_reservation(): void
    {
        $this->withoutExceptionHandling();
        $this->seed(DatabaseSeeder::class);
        $response = $this->postJson('/reservas', [
            'source'=>'online','contact_name'=>'Reserva Grupo','contact_email'=>'grupo@example.com','contact_whatsapp'=>'+56 999 111 222',
            'preferred_language' => 'es', 'tour_package_id' => 3,
            'tour_date' => now()->addDays(7)->toDateString(),
            'travelers'=>[
                ['full_name'=>'Test Traveler','nationality'=>'Chile','passport_number'=>'CL-NEW-2026','apparel_size'=>'M'],
                ['full_name'=>'Second Traveler','nationality'=>'Perú','passport_number'=>'PE-NEW-2026','apparel_size'=>'L'],
            ],
        ]);
        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('reservations', ['source' => 'online', 'status' => 'pending_equipment']);
        $this->assertDatabaseHas('departures', ['tour_package_id' => 3, 'tour_date' => now()->addDays(7)->toDateString()]);
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('reservations', 5);
    }

    public function test_receptionist_can_complete_a_tourist_and_admin_can_close_the_departure(): void
    {
        $this->seed(DatabaseSeeder::class);
        $session = ['staff_id'=>2,'staff_role'=>'receptionist','staff_branch_id'=>1];
        $date = now()->addDays(4)->toDateString();
        $this->withSession($session)->putJson('/reservas/1/completar', [
            'tour_package_id'=>2, 'tour_date'=>$date, 'equipment_ids'=>[2,3], 'notes'=>'Prueba de equipo',
        ])->assertOk();
        $this->assertDatabaseHas('reservations',['id'=>1,'status'=>'confirmed','tour_package_id'=>2]);
        $departure = \Illuminate\Support\Facades\DB::table('departures')->where('tour_package_id',2)->where('tour_date',$date)->first();
        $adminSession = ['staff_id'=>1,'staff_role'=>'admin','staff_branch_id'=>1];
        $this->withSession($adminSession)->putJson('/salidas/'.$departure->id.'/cerrar', [
            'includes'=>'Transporte y alimentación','excludes'=>'Propinas','guide'=>'Guía Demo','driver'=>'Conductor Demo','observations'=>'Sin novedad',
        ])->assertOk();
        $this->assertDatabaseHas('departures',['id'=>$departure->id,'status'=>'closed']);
    }

    public function test_receptionist_has_a_limited_read_only_panel(): void
    {
        $this->seed(DatabaseSeeder::class);
        $session = ['staff_id'=>2,'staff_role'=>'receptionist','staff_branch_id'=>1];
        $this->withSession($session)->get('/panel')->assertOk()
            ->assertSee('Recepción de turistas',false)
            ->assertSee('data-view="departures"',false)
            ->assertSee('id="view-departures"',false)
            ->assertSee('Consulta las salidas y los viajeros asignados a tu sucursal.',false)
            ->assertSee('Equipamiento',false)
            ->assertSee('Hospedajes',false)
            ->assertDontSee('Nuevo empleado',false)
            ->assertDontSee('Nueva sucursal',false)
            ->assertDontSee('data-close-departure',false)
            ->assertDontSee('Editar</button>',false);
        $this->withSession($session)->putJson('/catalogo/packages/1', [
            'name'=>'Uyuni Premium','tour_category_id'=>1,'location'=>'Potosí','duration_days'=>3,'price'=>1590,'description'=>'Actualizado',
        ])->assertForbidden();
        $this->withSession($session)->putJson('/catalogo/employees/3', [
            'full_name'=>'Camila Quispe','email'=>'camila@incaland.bo','phone'=>'+591 700 000','role'=>'receptionist','branch_id'=>2,
        ])->assertForbidden();
        $this->withSession($session)->putJson('/salidas/1/cerrar', [
            'includes'=>'x','excludes'=>'x','guide'=>'x','driver'=>'x',
        ])->assertForbidden();
        $this->withSession($session)->putJson('/reservas/2/completar', [
            'tour_package_id'=>1,'tour_date'=>now()->addDays(3)->toDateString(),'equipment_ids'=>[],
        ])->assertForbidden();
        $this->assertDatabaseHas('tour_packages',['id'=>1,'name'=>'Salar de Uyuni']);
    }

    public function test_logout_works_even_when_the_page_token_has_expired(): void
    {
        $this->withSession(['staff_id'=>1,'staff_role'=>'admin','staff_branch_id'=>1])
            ->post('/salir')
            ->assertRedirect('/ingresar')
            ->assertHeader('Pragma', 'no-cache');
        $this->assertFalse(session()->has('staff_id'));
    }

    public function test_staff_passwords_use_their_first_name_followed_by_1234(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post('/ingresar', ['email'=>'maria@incaland.bo', 'password'=>'demo1234'])
            ->assertSessionHasErrors('email');

        $this->post('/ingresar', ['email'=>'maria@incaland.bo', 'password'=>'maria1234'])
            ->assertRedirect('/panel');

        $this->post('/salir')->assertRedirect('/ingresar');

        $this->post('/ingresar', ['email'=>'daniel@incaland.bo', 'password'=>'daniel1234'])
            ->assertRedirect('/panel');
    }

    public function test_admin_can_update_profile_and_login_with_username(): void
    {
        $this->seed(DatabaseSeeder::class);
        $session = ['staff_id'=>1,'staff_role'=>'admin','staff_branch_id'=>1];

        $this->withSession($session)->putJson('/perfil', [
            'full_name'=>'Administradora Incaland',
            'username'=>'admin.incaland',
            'email'=>'admin@incaland.bo',
            'current_password'=>'maria1234',
            'password'=>'NuevaClave2026',
            'password_confirmation'=>'NuevaClave2026',
        ])->assertOk()->assertJsonPath('ok', true);

        $staff = \Illuminate\Support\Facades\DB::table('employees')->where('id', 1)->first();
        $this->assertSame('admin.incaland', $staff->username);
        $this->assertSame('admin@incaland.bo', $staff->email);
        $this->assertTrue(Hash::check('NuevaClave2026', $staff->password));

        $this->post('/salir');
        $this->post('/ingresar', ['email'=>'admin.incaland', 'password'=>'NuevaClave2026'])
            ->assertRedirect('/panel');
    }

    public function test_profile_requires_current_password_and_api_docs_are_admin_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = ['staff_id'=>1,'staff_role'=>'admin','staff_branch_id'=>1];
        $reception = ['staff_id'=>2,'staff_role'=>'receptionist','staff_branch_id'=>1];

        $this->withSession($admin)->putJson('/perfil', [
            'full_name'=>'María Flores','username'=>'maria','email'=>'otro@incaland.bo',
            'current_password'=>'incorrecta','password'=>'','password_confirmation'=>'',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->get('/documentacion-api')->assertRedirect('/ingresar');
        $this->withSession($reception)->get('/documentacion-api')->assertForbidden();
        $this->withSession($admin)->get('/documentacion-api')->assertOk()->assertSee('API REST', false);
    }

    public function test_login_and_traveler_forms_show_home_link_without_demo_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/ingresar')->assertOk()
            ->assertSee('Volver al inicio', false)
            ->assertDontSee('Acceso de demostración', false)
            ->assertDontSee('maria1234', false);
        $this->get('/reservar')->assertOk()->assertSee('Volver al inicio', false);
    }

    public function test_session_pages_are_not_cached_and_logged_out_staff_cannot_return_to_the_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        $panel = $this->withSession(['staff_id'=>1,'staff_role'=>'admin','staff_branch_id'=>1])
            ->get('/panel');

        $panel->assertOk();
        $this->assertStringContainsString('no-store', $panel->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', $panel->headers->get('Cache-Control'));

        $this->post('/salir')->assertRedirect('/ingresar');
        $this->get('/panel')->assertRedirect('/ingresar');

        $login = $this->get('/ingresar');
        $login->assertOk();
        $this->assertStringContainsString('no-store', $login->headers->get('Cache-Control'));
    }
}
