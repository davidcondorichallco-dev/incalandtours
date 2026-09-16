<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TourismController extends Controller
{
    public function dashboard()
    {
        $staff = DB::table('employees')->where('id', session('staff_id'))->firstOrFail();
        return view('dashboard', $this->data($staff) + ['staff' => $staff]);
    }

    public function publicHome()
    {
        $packages = DB::table('tour_packages as p')
            ->join('tour_categories as c','c.id','=','p.tour_category_id')
            ->where('p.active',true)
            ->select('p.*','c.name as category_name','c.color as category_color')
            ->get();
        $slides = DB::table('home_slides')->where('active', true)->orderBy('display_order')->orderBy('id')->get();
        return view('home', ['packages'=>$packages, 'categories'=>DB::table('tour_categories')->get(), 'slides'=>$slides]);
    }

    public function publicForm(string $token)
    {
        $branch = DB::table('branches')->where('qr_token', $token)->where('active', true)->firstOrFail();
        return view('tourist-form', ['branch' => $branch, 'packages' => DB::table('tour_packages')->where('active', true)->get(), 'online' => false]);
    }

    public function onlineForm()
    {
        return view('tourist-form', ['branch' => null, 'packages' => DB::table('tour_packages')->where('active', true)->get(), 'online' => true, 'selectedPackage'=>(int) request('package')]);
    }

    public function storeTourist(Request $request)
    {
        $online = $request->string('source')->toString() === 'online';
        if ($online) return $this->storeOnlineBooking($request);

        $rules = [
            'full_name' => 'required|string|max:150', 'nationality' => 'required|string|max:80',
            'passport_number' => 'required|string|max:60', 'food_notes' => 'nullable|string|max:500',
            'apparel_size' => 'nullable|string|max:20', 'height_cm' => 'nullable|integer|min:80|max:240',
            'hotel' => 'nullable|string|max:150', 'whatsapp' => 'required|string|max:40',
            'preferred_language' => 'nullable|in:es,en,fr,pt,de', 'branch_token' => 'nullable|uuid',
        ];
        $data = $request->validate($rules);

        $reservationId = DB::transaction(function () use ($data) {
            DB::table('tourists')->updateOrInsert(
                ['passport_number' => $data['passport_number']],
                collect($data)->only(['full_name','nationality','food_notes','apparel_size','height_cm','hotel','whatsapp','preferred_language'])->merge(['updated_at' => now(), 'created_at' => now()])->all()
            );
            $tourist = DB::table('tourists')->where('passport_number', $data['passport_number'])->first();
            $branchId = isset($data['branch_token']) ? DB::table('branches')->where('qr_token', $data['branch_token'])->value('id') : null;
            return DB::table('reservations')->insertGetId([
                'tourist_id'=>$tourist->id, 'branch_id'=>$branchId, 'source'=>'qr',
                'status'=>'pending_package',
                'created_at'=>now(), 'updated_at'=>now(),
            ]);
        });

        return response()->json(['ok' => true, 'reservation_id' => $reservationId, 'message' => 'Registro recibido por la sucursal.']);
    }

    private function storeOnlineBooking(Request $request)
    {
        $data = $request->validate([
            'contact_name'=>'required|string|max:150','contact_email'=>'required|email|max:150','contact_whatsapp'=>'required|string|max:40',
            'tour_package_id'=>'required|exists:tour_packages,id','tour_date'=>'required|date|after_or_equal:today',
            'preferred_language'=>'nullable|in:es,en,fr,pt,de','travelers'=>'required|array|min:1|max:20',
            'travelers.*.full_name'=>'required|string|max:150','travelers.*.nationality'=>'required|string|max:80',
            'travelers.*.passport_number'=>'required|string|max:60|distinct','travelers.*.food_notes'=>'nullable|string|max:500',
            'travelers.*.apparel_size'=>'nullable|string|max:20','travelers.*.height_cm'=>'nullable|integer|min:80|max:240',
            'travelers.*.hotel'=>'nullable|string|max:150','travelers.*.whatsapp'=>'nullable|string|max:40',
        ]);

        $booking = DB::transaction(function () use ($data) {
            $departureId = DB::table('departures')->where('tour_package_id',$data['tour_package_id'])->where('tour_date',$data['tour_date'])->value('id');
            if (!$departureId) $departureId = DB::table('departures')->insertGetId(['tour_package_id'=>$data['tour_package_id'],'tour_date'=>$data['tour_date'],'status'=>'open','created_at'=>now(),'updated_at'=>now()]);
            $reference = 'ILT-'.strtoupper(Str::random(7));
            $bookingId = DB::table('bookings')->insertGetId([
                'reference'=>$reference,'tour_package_id'=>$data['tour_package_id'],'departure_id'=>$departureId,
                'contact_name'=>$data['contact_name'],'contact_email'=>$data['contact_email'],'contact_whatsapp'=>$data['contact_whatsapp'],
                'tour_date'=>$data['tour_date'],'status'=>'pending_equipment','created_at'=>now(),'updated_at'=>now(),
            ]);
            foreach ($data['travelers'] as $travelerData) {
                DB::table('tourists')->updateOrInsert(['passport_number'=>$travelerData['passport_number']], [
                    'full_name'=>$travelerData['full_name'],'nationality'=>$travelerData['nationality'],
                    'food_notes'=>$travelerData['food_notes'] ?? null,'apparel_size'=>$travelerData['apparel_size'] ?? null,
                    'height_cm'=>$travelerData['height_cm'] ?? null,'hotel'=>$travelerData['hotel'] ?? null,
                    'whatsapp'=>($travelerData['whatsapp'] ?? null) ?: $data['contact_whatsapp'],'preferred_language'=>$data['preferred_language'] ?? 'es',
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
                $touristId = DB::table('tourists')->where('passport_number',$travelerData['passport_number'])->value('id');
                DB::table('reservations')->insert([
                    'booking_id'=>$bookingId,'tourist_id'=>$touristId,'tour_package_id'=>$data['tour_package_id'],'departure_id'=>$departureId,
                    'source'=>'online','status'=>'pending_equipment','tour_date'=>$data['tour_date'],'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
            return (object) ['id'=>$bookingId,'reference'=>$reference,'travelers'=>count($data['travelers'])];
        });
        return response()->json(['ok'=>true,'booking_id'=>$booking->id,'reference'=>$booking->reference,'message'=>"Reserva {$booking->reference} recibida para {$booking->travelers} viajero(s). El equipamiento se asignará al presentarse."]);
    }

    public function completeReservation(Request $request, int $id)
    {
        if (session('staff_role') === 'receptionist') {
            $allowed = DB::table('reservations')->where('id',$id)->where(function ($query) {
                $query->where('branch_id',session('staff_branch_id'))->orWhereNull('branch_id');
            })->exists();
            abort_unless($allowed,403);
        }
        $data = $request->validate([
            'tour_package_id'=>'required|exists:tour_packages,id', 'tour_date'=>'required|date',
            'equipment_ids'=>'array', 'equipment_ids.*'=>'exists:equipment,id', 'notes'=>'nullable|string|max:500',
        ]);
        DB::transaction(function () use ($data, $id) {
            $departureId = DB::table('departures')->where('tour_package_id',$data['tour_package_id'])->where('tour_date',$data['tour_date'])->value('id');
            if (!$departureId) $departureId = DB::table('departures')->insertGetId(['tour_package_id'=>$data['tour_package_id'],'tour_date'=>$data['tour_date'],'status'=>'open','created_at'=>now(),'updated_at'=>now()]);
            DB::table('reservations')->where('id',$id)->update(['tour_package_id'=>$data['tour_package_id'],'tour_date'=>$data['tour_date'],'departure_id'=>$departureId,'status'=>'confirmed','reception_notes'=>$data['notes'] ?? null,'updated_at'=>now()]);
            DB::table('equipment_reservation')->where('reservation_id',$id)->delete();
            foreach ($data['equipment_ids'] ?? [] as $equipmentId) {
                DB::table('equipment_reservation')->insert(['reservation_id'=>$id,'equipment_id'=>$equipmentId,'quantity'=>1,'created_at'=>now(),'updated_at'=>now()]);
            }
            $bookingId = DB::table('reservations')->where('id',$id)->value('booking_id');
            if ($bookingId && !DB::table('reservations')->where('booking_id',$bookingId)->where('status','!=','confirmed')->exists()) {
                DB::table('bookings')->where('id',$bookingId)->update(['status'=>'confirmed','updated_at'=>now()]);
            }
        });
        return response()->json(['ok'=>true,'message'=>'Turista confirmado y agregado a la salida.']);
    }

    public function closeDeparture(Request $request, int $id)
    {
        $data = $request->validate(['includes'=>'required|string','excludes'=>'required|string','observations'=>'nullable|string','guide'=>'required|string|max:120','driver'=>'required|string|max:120']);
        DB::table('departures')->where('id',$id)->update($data + ['status'=>'closed','closed_at'=>now(),'updated_at'=>now()]);
        return response()->json(['ok'=>true,'message'=>'Salida cerrada y lista para operar.']);
    }

    public function storeResource(Request $request, string $type)
    {
        $map = $this->resourceMap();
        abort_unless(isset($map[$type]), 404);
        $config = $map[$type];
        $data = $request->validate($config['rules']);
        if ($type === 'employees') $data['password'] = Hash::make($data['password']);
        if ($request->hasFile('image')) {
            $data['image_path'] = '/storage/'.$request->file('image')->store('catalog','public');
        }
        unset($data['image']);
        if ($type === 'equipment') $data['available_stock'] = $data['stock'];
        $id = DB::table($config['table'])->insertGetId($data + $config['extra'] + ['created_at'=>now(),'updated_at'=>now()]);
        return response()->json(['ok'=>true,'id'=>$id,'message'=>'Registro creado correctamente.']);
    }

    public function updateResource(Request $request, string $type, int $id)
    {
        $map = $this->resourceMap(true, $id);
        abort_unless(isset($map[$type]),404);
        $config = $map[$type];
        abort_unless(DB::table($config['table'])->where('id',$id)->exists(),404);
        $data = $request->validate($config['rules']);
        if ($type === 'employees') {
            if (!empty($data['password'])) $data['password'] = Hash::make($data['password']); else unset($data['password']);
        }
        if ($request->hasFile('image')) $data['image_path'] = '/storage/'.$request->file('image')->store('catalog','public');
        unset($data['image']);
        if ($type === 'equipment' && isset($data['stock'])) {
            $current = DB::table('equipment')->where('id',$id)->first();
            $assigned = max(0,$current->stock-$current->available_stock);
            $data['available_stock'] = max(0,$data['stock']-$assigned);
        }
        DB::table($config['table'])->where('id',$id)->update($data + ['updated_at'=>now()]);
        return response()->json(['ok'=>true,'message'=>'Cambios guardados correctamente.']);
    }

    public function destroySlide(int $id)
    {
        $slide = DB::table('home_slides')->where('id', $id)->first();
        abort_unless($slide, 404);

        DB::table('home_slides')->where('id', $id)->delete();
        if (str_starts_with($slide->image_path, '/storage/')) {
            Storage::disk('public')->delete(substr($slide->image_path, strlen('/storage/')));
        }

        return response()->json(['ok'=>true, 'message'=>'Diapositiva eliminada correctamente.']);
    }

    private function resourceMap(bool $updating = false, ?int $id = null): array
    {
        return [
            'branches'=>['table'=>'branches','rules'=>['name'=>'required|string','city'=>'required|string','address'=>'required|string','phone'=>'nullable|string'],'extra'=>['qr_token'=>Str::uuid(),'active'=>true]],
            'categories'=>['table'=>'tour_categories','rules'=>['name'=>'required|string','color'=>'nullable|string'],'extra'=>[]],
            'packages'=>['table'=>'tour_packages','rules'=>['name'=>'required|string','tour_category_id'=>'required|exists:tour_categories,id','location'=>'required|string','duration_days'=>'required|integer|min:1','price'=>'required|numeric|min:0','description'=>'nullable|string','image'=>'nullable|image|max:5120'],'extra'=>['active'=>true]],
            'equipment'=>['table'=>'equipment','rules'=>['name'=>'required|string','category'=>'required|string','size'=>'nullable|string','stock'=>'required|integer|min:0','condition'=>'nullable|string','image'=>'nullable|image|max:5120'],'extra'=>[]],
            'lodgings'=>['table'=>'lodgings','rules'=>['name'=>'required|string','city'=>'required|string','address'=>'required|string','rooms'=>'required|integer|min:0','available_rooms'=>'required|integer|min:0','image'=>'nullable|image|max:5120'],'extra'=>['active'=>true]],
            'employees'=>['table'=>'employees','rules'=>['full_name'=>'required|string','username'=>['required','string','min:3','max:60','regex:/^[A-Za-z0-9._-]+$/',Rule::unique('employees','username')->ignore($id)],'email'=>['required','email',Rule::unique('employees','email')->ignore($id)],'phone'=>'nullable|string','role'=>'required|in:admin,receptionist','branch_id'=>'required|exists:branches,id','password'=>$updating?'nullable|string|min:8':'required|string|min:8'],'extra'=>['active'=>true]],
            'slides'=>['table'=>'home_slides','rules'=>['title'=>'required|string|max:140','subtitle'=>'nullable|string|max:280','display_order'=>'required|integer|min:0|max:999','active'=>'required|boolean','image'=>$updating?'nullable|image|max:8192':'required|image|max:8192'],'extra'=>[]],
        ];
    }

    private function data(object $staff): array
    {
        $reservationQuery = DB::table('reservations as r')->join('tourists as t','t.id','=','r.tourist_id')->leftJoin('branches as b','b.id','=','r.branch_id')->leftJoin('tour_packages as p','p.id','=','r.tour_package_id')->select('r.*','t.full_name','t.nationality','t.passport_number','t.food_notes','t.apparel_size','t.height_cm','t.hotel','t.whatsapp','b.name as branch_name','p.name as package_name')->orderByDesc('r.created_at');
        if ($staff->role === 'receptionist') $reservationQuery->where(function ($query) use ($staff) { $query->where('r.branch_id',$staff->branch_id)->orWhereNull('r.branch_id'); });
        $reservations = $reservationQuery->get();
        $departures = DB::table('departures as d')->join('tour_packages as p','p.id','=','d.tour_package_id')->select('d.*','p.name as package_name','p.location')->orderBy('d.tour_date')->get()->map(function ($departure) use ($staff) {
            $tourists = DB::table('reservations as r')->join('tourists as t','t.id','=','r.tourist_id')->leftJoin('branches as b','b.id','=','r.branch_id')->where('r.departure_id',$departure->id)->select('r.id','r.status','r.source','t.full_name','t.nationality','t.apparel_size','b.name as branch_name');
            if ($staff->role === 'receptionist') $tourists->where(function ($query) use ($staff) { $query->where('r.branch_id',$staff->branch_id)->orWhereNull('r.branch_id'); });
            $departure->tourists = $tourists->get();
            return $departure;
        });
        return [
            'branches'=>DB::table('branches')->get(), 'employees'=>DB::table('employees')->get(),
            'categories'=>DB::table('tour_categories')->get(), 'packages'=>DB::table('tour_packages as p')->join('tour_categories as c','c.id','=','p.tour_category_id')->select('p.*','c.name as category_name','c.color as category_color')->get(),
            'equipment'=>DB::table('equipment')->get(), 'lodgings'=>DB::table('lodgings')->get(),
            'slides'=>DB::table('home_slides')->orderBy('display_order')->orderBy('id')->get(),
            'reservations'=>$reservations, 'departures'=>$departures,
        ];
    }
}
