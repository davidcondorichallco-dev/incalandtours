<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function dashboard(Request $request)
    {
        $staff = $request->attributes->get('staff');
        $reservations = $this->reservationQuery($staff)->get();

        return response()->json(['ok'=>true, 'data'=>[
            'staff'=>[
                'id'=>$staff->id,
                'full_name'=>$staff->full_name,
                'role'=>$staff->role,
                'branch_id'=>$staff->branch_id,
                'branch_name'=>$staff->branch_name,
            ],
            'summary'=>[
                'pending_reservations'=>$reservations->whereIn('status', ['pending_package','pending_equipment'])->count(),
                'online_reservations'=>$reservations->where('source', 'online')->count(),
                'open_departures'=>DB::table('departures')->where('status', 'open')->count(),
                'available_equipment'=>DB::table('equipment')->sum('available_stock'),
                'total_equipment'=>DB::table('equipment')->sum('stock'),
            ],
            'recent_reservations'=>$reservations->take(4)->values(),
            'next_departure'=>$this->departureCollection($staff)->first(),
        ]]);
    }

    public function reservations(Request $request)
    {
        $query = $this->reservationQuery($request->attributes->get('staff'));

        if ($request->filled('status')) $query->where('r.status', $request->string('status')->toString());
        if ($request->filled('source')) $query->where('r.source', $request->string('source')->toString());
        if ($request->filled('search')) {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(function (Builder $query) use ($search) {
                $query->where('t.full_name', 'like', $search)->orWhere('t.passport_number', 'like', $search);
            });
        }

        $reservations = $query->get()->each(fn ($reservation) => $this->addEquipment($reservation));

        return response()->json(['ok'=>true, 'data'=>$reservations]);
    }

    public function reservation(Request $request, int $id)
    {
        $reservation = $this->reservationQuery($request->attributes->get('staff'))->where('r.id', $id)->first();

        if (!$reservation) return response()->json(['ok'=>false, 'message'=>'Reserva no encontrada.'], 404);

        $this->addEquipment($reservation);
        return response()->json(['ok'=>true, 'data'=>$reservation]);
    }

    public function completeReservation(Request $request, int $id)
    {
        $staff = $request->attributes->get('staff');
        $reservation = $this->reservationQuery($staff)->where('r.id', $id)->first();
        if (!$reservation) return response()->json(['ok'=>false, 'message'=>'Reserva no encontrada o fuera de tu sucursal.'], 404);

        $data = $request->validate([
            'tour_package_id'=>'required|exists:tour_packages,id',
            'tour_date'=>'required|date',
            'equipment_ids'=>'array',
            'equipment_ids.*'=>'exists:equipment,id',
            'notes'=>'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($data, $id) {
            $departureId = DB::table('departures')->where('tour_package_id', $data['tour_package_id'])->where('tour_date', $data['tour_date'])->value('id');
            if (!$departureId) {
                $departureId = DB::table('departures')->insertGetId([
                    'tour_package_id'=>$data['tour_package_id'],
                    'tour_date'=>$data['tour_date'],
                    'status'=>'open',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }

            DB::table('reservations')->where('id', $id)->update([
                'tour_package_id'=>$data['tour_package_id'],
                'tour_date'=>$data['tour_date'],
                'departure_id'=>$departureId,
                'status'=>'confirmed',
                'reception_notes'=>$data['notes'] ?? null,
                'updated_at'=>now(),
            ]);

            DB::table('equipment_reservation')->where('reservation_id', $id)->delete();
            foreach ($data['equipment_ids'] ?? [] as $equipmentId) {
                DB::table('equipment_reservation')->insert([
                    'reservation_id'=>$id,
                    'equipment_id'=>$equipmentId,
                    'quantity'=>1,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }

            $bookingId = DB::table('reservations')->where('id', $id)->value('booking_id');
            if ($bookingId && !DB::table('reservations')->where('booking_id', $bookingId)->where('status', '!=', 'confirmed')->exists()) {
                DB::table('bookings')->where('id', $bookingId)->update(['status'=>'confirmed', 'updated_at'=>now()]);
            }
        });

        return response()->json(['ok'=>true, 'message'=>'Turista confirmado y agregado a la salida.']);
    }

    public function departures(Request $request)
    {
        $departures = $this->departureCollection($request->attributes->get('staff'));

        if ($request->filled('date')) $departures = $departures->where('tour_date', $request->string('date')->toString());
        if ($request->filled('package_id')) $departures = $departures->where('tour_package_id', (int) $request->input('package_id'));
        if ($request->filled('status')) $departures = $departures->where('status', $request->string('status')->toString());

        return response()->json(['ok'=>true, 'data'=>$departures->values()]);
    }

    public function departure(Request $request, int $id)
    {
        $departure = $this->departureCollection($request->attributes->get('staff'), $id)->first();
        return $departure
            ? response()->json(['ok'=>true, 'data'=>$departure])
            : response()->json(['ok'=>false, 'message'=>'Salida no encontrada.'], 404);
    }

    public function closeDeparture(Request $request, int $id)
    {
        if (!DB::table('departures')->where('id', $id)->exists()) {
            return response()->json(['ok'=>false, 'message'=>'Salida no encontrada.'], 404);
        }

        $data = $request->validate([
            'includes'=>'required|string',
            'excludes'=>'required|string',
            'observations'=>'nullable|string',
            'guide'=>'required|string|max:120',
            'driver'=>'required|string|max:120',
        ]);

        DB::table('departures')->where('id', $id)->update($data + ['status'=>'closed', 'closed_at'=>now(), 'updated_at'=>now()]);

        return response()->json(['ok'=>true, 'message'=>'Salida cerrada y lista para operar.']);
    }

    private function reservationQuery(object $staff): Builder
    {
        $query = DB::table('reservations as r')
            ->join('tourists as t', 't.id', '=', 'r.tourist_id')
            ->leftJoin('branches as b', 'b.id', '=', 'r.branch_id')
            ->leftJoin('tour_packages as p', 'p.id', '=', 'r.tour_package_id')
            ->leftJoin('bookings as bk', 'bk.id', '=', 'r.booking_id')
            ->select('r.*', 't.full_name', 't.nationality', 't.passport_number', 't.food_notes', 't.apparel_size', 't.height_cm', 't.hotel', 't.whatsapp', 't.preferred_language', 'b.name as branch_name', 'p.name as package_name', 'bk.reference as booking_reference')
            ->orderByDesc('r.created_at');

        if ($staff->role === 'receptionist') {
            $query->where(function (Builder $query) use ($staff) {
                $query->where('r.branch_id', $staff->branch_id)->orWhereNull('r.branch_id');
            });
        }

        return $query;
    }

    private function departureCollection(object $staff, ?int $id = null)
    {
        $query = DB::table('departures as d')
            ->join('tour_packages as p', 'p.id', '=', 'd.tour_package_id')
            ->select('d.*', 'p.name as package_name', 'p.location')
            ->orderBy('d.tour_date');
        if ($id) $query->where('d.id', $id);

        return $query->get()->map(function ($departure) use ($staff) {
            $tourists = DB::table('reservations as r')
                ->join('tourists as t', 't.id', '=', 'r.tourist_id')
                ->leftJoin('branches as b', 'b.id', '=', 'r.branch_id')
                ->where('r.departure_id', $departure->id)
                ->select('r.id as reservation_id', 'r.status', 'r.source', 't.full_name', 't.nationality', 't.apparel_size', 'b.name as branch_name');

            if ($staff->role === 'receptionist') {
                $tourists->where(function (Builder $query) use ($staff) {
                    $query->where('r.branch_id', $staff->branch_id)->orWhereNull('r.branch_id');
                });
            }

            $departure->tourists = $tourists->get();
            $departure->tourists_count = $departure->tourists->count();
            return $departure;
        });
    }

    private function addEquipment(object $reservation): void
    {
        $reservation->equipment = DB::table('equipment_reservation as er')
            ->join('equipment as e', 'e.id', '=', 'er.equipment_id')
            ->where('er.reservation_id', $reservation->id)
            ->select('e.id', 'e.name', 'e.category', 'er.quantity', 'er.assigned_size')
            ->get();
    }
}
