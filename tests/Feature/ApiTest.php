<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_visual_documentation_is_available_only_to_an_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/documentacion-api')->assertRedirect('/ingresar');
        $this->withSession(['staff_id'=>2,'staff_role'=>'receptionist','staff_branch_id'=>1])
            ->get('/documentacion-api')->assertForbidden();
        $this->withSession(['staff_id'=>1,'staff_role'=>'admin','staff_branch_id'=>1])
            ->get('/documentacion-api')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('API REST', false)
            ->assertSee('/public/reservations/qr/{token}', false);

        $this->get('/api/documentacion')->assertNotFound();
    }

    public function test_public_api_exposes_catalog_and_accepts_qr_and_online_reservations(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->getJson('/api/v1/public/catalog')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonCount(3, 'data.packages')
            ->assertJsonCount(3, 'data.carousel');

        $this->getJson('/api/v1/public/home')
            ->assertOk()
            ->assertJsonCount(3, 'data.carousel')
            ->assertJsonPath('data.carousel.0.display_order', 1);

        $token = DB::table('branches')->where('id', 1)->value('qr_token');
        $this->getJson('/api/v1/public/qr/'.$token)
            ->assertOk()
            ->assertJsonPath('data.branch.id', 1);

        $this->postJson('/api/v1/public/reservations/qr/'.$token, [
            'full_name'=>'Viajero API',
            'nationality'=>'Perú',
            'passport_number'=>'API-QR-001',
            'whatsapp'=>'+51 999 000 111',
            'preferred_language'=>'es',
        ])->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('reservations', ['branch_id'=>1, 'source'=>'qr', 'status'=>'pending_package']);

        $this->postJson('/api/v1/public/reservations/online', [
            'contact_name'=>'Grupo Flutter',
            'contact_email'=>'flutter@example.com',
            'contact_whatsapp'=>'+591 700 10000',
            'tour_package_id'=>1,
            'tour_date'=>now()->addDays(10)->toDateString(),
            'preferred_language'=>'es',
            'travelers'=>[[
                'full_name'=>'Reserva Flutter',
                'nationality'=>'Bolivia',
                'passport_number'=>'API-ONLINE-001',
                'apparel_size'=>'M',
            ]],
        ])->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('bookings', ['contact_email'=>'flutter@example.com']);
    }

    public function test_bearer_token_authentication_can_open_and_close_an_api_session(): void
    {
        $this->seed(DatabaseSeeder::class);

        $login = $this->postJson('/api/v1/auth/login', [
            'email'=>'maria@incaland.bo',
            'password'=>'maria1234',
            'device_name'=>'Prueba Flutter',
        ])->assertOk()->assertJsonPath('data.staff.role', 'admin');

        $token = $login->json('data.access_token');
        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'maria@incaland.bo');

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_receptionist_can_use_operations_but_cannot_run_admin_actions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->loginToken('daniel@incaland.bo', 'daniel1234');

        $this->withToken($token)->getJson('/api/v1/dashboard')->assertOk();
        $this->withToken($token)->getJson('/api/v1/reservations')->assertOk();
        $this->withToken($token)->getJson('/api/v1/departures')->assertOk();
        $this->withToken($token)->getJson('/api/v1/catalog/equipment')->assertOk();
        $this->withToken($token)->getJson('/api/v1/catalog/employees')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/catalog/slides')->assertForbidden();
        $this->withToken($token)->putJson('/api/v1/departures/1/close', [
            'includes'=>'Transporte',
            'excludes'=>'Propinas',
            'guide'=>'Guía',
            'driver'=>'Conductor',
        ])->assertForbidden();

        $this->withToken($token)->putJson('/api/v1/reservations/1/complete', [
            'tour_package_id'=>2,
            'tour_date'=>now()->addDays(6)->toDateString(),
            'equipment_ids'=>[2],
            'notes'=>'Confirmado desde Flutter',
        ])->assertOk();
        $this->assertDatabaseHas('reservations', ['id'=>1, 'status'=>'confirmed', 'tour_package_id'=>2]);
    }

    public function test_admin_api_manages_catalog_departures_and_branch_qr(): void
    {
        $this->seed(DatabaseSeeder::class);
        $token = $this->loginToken('maria@incaland.bo', 'maria1234');

        $created = $this->withToken($token)->postJson('/api/v1/catalog/categories', [
            'name'=>'Gastronomía',
            'color'=>'#8B4513',
        ])->assertCreated()->assertJsonPath('data.name', 'Gastronomía');

        $categoryId = $created->json('data.id');
        $this->withToken($token)->putJson('/api/v1/catalog/categories/'.$categoryId, [
            'name'=>'Gastronomía local',
            'color'=>'#7A3E12',
        ])->assertOk()->assertJsonPath('data.name', 'Gastronomía local');

        $this->withToken($token)->getJson('/api/v1/branches/1/qr')
            ->assertOk()
            ->assertJsonStructure(['data'=>['qr_token','qr_payload','registration_url','api_form_url','api_submit_url','qr_image_url']]);

        $this->withToken($token)->putJson('/api/v1/departures/1/close', [
            'includes'=>'Transporte y alimentación',
            'excludes'=>'Propinas',
            'guide'=>'Julio Mamani',
            'driver'=>'Óscar Rojas',
            'observations'=>'Cerrada desde API',
        ])->assertOk();
        $this->assertDatabaseHas('departures', ['id'=>1, 'status'=>'closed']);
    }

    public function test_admin_api_manages_carousel_images_with_multipart_requests(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $token = $this->loginToken('maria@incaland.bo', 'maria1234');

        $created = $this->withToken($token)->post('/api/v1/catalog/slides', [
            'title'=>'Nueva portada móvil',
            'subtitle'=>'Contenido administrado completamente desde la aplicación.',
            'display_order'=>4,
            'active'=>1,
            'image'=>new UploadedFile(public_path('images/bolivia-hero.png'), 'portada.png', 'image/png', null, true),
        ])->assertCreated()->assertJsonPath('data.title', 'Nueva portada móvil');

        $slideId = $created->json('data.id');
        $firstPath = DB::table('home_slides')->where('id', $slideId)->value('image_path');
        Storage::disk('public')->assertExists(substr($firstPath, strlen('/storage/')));

        $updated = $this->withToken($token)->post('/api/v1/catalog/slides/'.$slideId, [
            '_method'=>'PUT',
            'title'=>'Portada móvil actualizada',
            'subtitle'=>'Nueva imagen y nuevo contenido.',
            'display_order'=>5,
            'active'=>0,
            'image'=>new UploadedFile(public_path('images/carousel-sajama.png'), 'sajama.png', 'image/png', null, true),
        ])->assertOk()->assertJsonPath('data.active', 0);

        $secondPath = $updated->json('data.image_path');
        Storage::disk('public')->assertMissing(substr($firstPath, strlen('/storage/')));
        Storage::disk('public')->assertExists(substr($secondPath, strlen('/storage/')));

        $this->withToken($token)->deleteJson('/api/v1/catalog/slides/'.$slideId)->assertOk();
        $this->assertDatabaseMissing('home_slides', ['id'=>$slideId]);
        Storage::disk('public')->assertMissing(substr($secondPath, strlen('/storage/')));
    }

    private function loginToken(string $email, string $password): string
    {
        return $this->postJson('/api/v1/auth/login', compact('email', 'password'))
            ->assertOk()
            ->json('data.access_token');
    }
}
