<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    public function index(Request $request, string $type)
    {
        if (!$this->isAllowed($type)) return response()->json(['ok'=>false, 'message'=>'Recurso no encontrado.'], 404);
        if (in_array($type, ['employees','slides'], true) && $request->attributes->get('staff')->role !== 'admin') {
            return response()->json(['ok'=>false, 'message'=>'Esta información requiere permisos de administrador.'], 403);
        }

        return response()->json(['ok'=>true, 'data'=>$this->collection($type)]);
    }

    public function show(Request $request, string $type, int $id)
    {
        if (!$this->isAllowed($type)) return response()->json(['ok'=>false, 'message'=>'Recurso no encontrado.'], 404);
        if (in_array($type, ['employees','slides'], true) && $request->attributes->get('staff')->role !== 'admin') {
            return response()->json(['ok'=>false, 'message'=>'Esta información requiere permisos de administrador.'], 403);
        }

        $resource = $this->collection($type)->firstWhere('id', $id);
        return $resource
            ? response()->json(['ok'=>true, 'data'=>$resource])
            : response()->json(['ok'=>false, 'message'=>'Registro no encontrado.'], 404);
    }

    public function store(Request $request, string $type)
    {
        $config = $this->config($type);
        if (!$config) return response()->json(['ok'=>false, 'message'=>'Recurso no encontrado.'], 404);

        $data = $request->validate($config['rules']);
        if ($type === 'employees') $data['password'] = Hash::make($data['password']);
        $this->storeImage($request, $data, $type);
        if ($type === 'equipment') $data['available_stock'] = $data['stock'];

        $id = DB::table($config['table'])->insertGetId($data + $config['extra'] + ['created_at'=>now(), 'updated_at'=>now()]);

        return response()->json([
            'ok'=>true,
            'message'=>'Registro creado correctamente.',
            'data'=>$this->collection($type)->firstWhere('id', $id),
        ], 201);
    }

    public function update(Request $request, string $type, int $id)
    {
        $config = $this->config($type, true);
        if (!$config) return response()->json(['ok'=>false, 'message'=>'Recurso no encontrado.'], 404);
        if (!DB::table($config['table'])->where('id', $id)->exists()) {
            return response()->json(['ok'=>false, 'message'=>'Registro no encontrado.'], 404);
        }

        $data = $request->validate($config['rules']);
        if ($type === 'employees') {
            if (!empty($data['password'])) $data['password'] = Hash::make($data['password']);
            else unset($data['password']);
        }
        $current = DB::table($config['table'])->where('id', $id)->first();
        $this->storeImage($request, $data, $type, $current);

        if ($type === 'equipment' && isset($data['stock'])) {
            $current = DB::table('equipment')->where('id', $id)->first();
            $assigned = max(0, $current->stock - $current->available_stock);
            $data['available_stock'] = max(0, $data['stock'] - $assigned);
        }

        DB::table($config['table'])->where('id', $id)->update($data + ['updated_at'=>now()]);

        return response()->json([
            'ok'=>true,
            'message'=>'Cambios guardados correctamente.',
            'data'=>$this->collection($type)->firstWhere('id', $id),
        ]);
    }

    public function destroySlide(int $id)
    {
        $slide = DB::table('home_slides')->where('id', $id)->first();
        if (!$slide) return response()->json(['ok'=>false, 'message'=>'Diapositiva no encontrada.'], 404);

        DB::table('home_slides')->where('id', $id)->delete();
        $this->deleteStoredImage($slide->image_path);

        return response()->json(['ok'=>true, 'message'=>'Diapositiva eliminada correctamente.']);
    }

    public function branchQr(int $id)
    {
        $branch = DB::table('branches')->where('id', $id)->first();
        if (!$branch) return response()->json(['ok'=>false, 'message'=>'Sucursal no encontrada.'], 404);

        $registrationUrl = route('tourist.qr', $branch->qr_token);
        $apiFormUrl = url('/api/v1/public/qr/'.$branch->qr_token);
        $apiSubmitUrl = url('/api/v1/public/reservations/qr/'.$branch->qr_token);

        return response()->json(['ok'=>true, 'data'=>[
            'branch_id'=>$branch->id,
            'branch_name'=>$branch->name,
            'qr_token'=>$branch->qr_token,
            'qr_payload'=>$registrationUrl,
            'registration_url'=>$registrationUrl,
            'api_form_url'=>$apiFormUrl,
            'api_submit_url'=>$apiSubmitUrl,
            'qr_image_url'=>'https://api.qrserver.com/v1/create-qr-code/?size=360x360&margin=10&data='.rawurlencode($registrationUrl),
        ]]);
    }

    private function isAllowed(string $type): bool
    {
        return in_array($type, ['branches','categories','packages','equipment','lodgings','employees','slides'], true);
    }

    private function collection(string $type)
    {
        $query = match ($type) {
            'branches' => DB::table('branches')->select('id','name','city','address','phone','active','created_at','updated_at'),
            'categories' => DB::table('tour_categories'),
            'packages' => DB::table('tour_packages as p')->join('tour_categories as c', 'c.id', '=', 'p.tour_category_id')->select('p.*','c.name as category_name','c.color as category_color'),
            'equipment' => DB::table('equipment'),
            'lodgings' => DB::table('lodgings'),
            'employees' => DB::table('employees as e')->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')->select('e.id','e.branch_id','e.full_name','e.email','e.phone','e.role','e.active','e.created_at','e.updated_at','b.name as branch_name'),
            'slides' => DB::table('home_slides')->orderBy('display_order')->orderBy('id'),
        };

        return $query->orderBy('id')->get()->each(function ($resource) {
            if (property_exists($resource, 'image_path')) {
                $resource->image_url = $resource->image_path ? url(ltrim($resource->image_path, '/')) : null;
            }
        });
    }

    private function config(string $type, bool $updating = false): ?array
    {
        return [
            'branches'=>['table'=>'branches','rules'=>['name'=>'required|string|max:150','city'=>'required|string|max:100','address'=>'required|string|max:200','phone'=>'nullable|string|max:40'],'extra'=>['qr_token'=>Str::uuid(),'active'=>true]],
            'categories'=>['table'=>'tour_categories','rules'=>['name'=>'required|string|max:100','color'=>'nullable|string|max:20'],'extra'=>[]],
            'packages'=>['table'=>'tour_packages','rules'=>['name'=>'required|string|max:150','tour_category_id'=>'required|exists:tour_categories,id','location'=>'required|string|max:150','duration_days'=>'required|integer|min:1|max:255','price'=>'required|numeric|min:0','description'=>'nullable|string','image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:8192'],'extra'=>['active'=>true]],
            'equipment'=>['table'=>'equipment','rules'=>['name'=>'required|string|max:150','category'=>'required|string|max:100','size'=>'nullable|string|max:100','stock'=>'required|integer|min:0','condition'=>'nullable|string|max:100','image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:8192'],'extra'=>[]],
            'lodgings'=>['table'=>'lodgings','rules'=>['name'=>'required|string|max:150','city'=>'required|string|max:100','address'=>'required|string|max:200','rooms'=>'required|integer|min:0','available_rooms'=>'required|integer|min:0','image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:8192'],'extra'=>['active'=>true]],
            'employees'=>['table'=>'employees','rules'=>['full_name'=>'required|string|max:150','email'=>'required|email|max:150','phone'=>'nullable|string|max:40','role'=>'required|in:admin,receptionist','branch_id'=>'required|exists:branches,id','password'=>$updating?'nullable|string|min:8':'required|string|min:8'],'extra'=>['active'=>true]],
            'slides'=>['table'=>'home_slides','rules'=>['title'=>'required|string|max:140','subtitle'=>'nullable|string|max:280','display_order'=>'required|integer|min:0|max:999','active'=>'required|boolean','image'=>$updating?'nullable|image|mimes:jpg,jpeg,png,webp|max:8192':'required|image|mimes:jpg,jpeg,png,webp|max:8192'],'extra'=>[]],
        ][$type] ?? null;
    }

    private function storeImage(Request $request, array &$data, string $type, ?object $current = null): void
    {
        if ($request->hasFile('image')) {
            $directory = $type === 'slides' ? 'carousel' : 'catalog/'.$type;
            $data['image_path'] = '/storage/'.$request->file('image')->store($directory, 'public');
            if ($current && property_exists($current, 'image_path')) $this->deleteStoredImage($current->image_path);
        }
        unset($data['image']);
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && str_starts_with($path, '/storage/')) {
            Storage::disk('public')->delete(substr($path, strlen('/storage/')));
        }
    }
}
