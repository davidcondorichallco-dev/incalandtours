<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TourismController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function catalog()
    {
        return response()->json(['ok'=>true, 'data'=>[
            'categories'=>DB::table('tour_categories')->orderBy('name')->get(),
            'packages'=>$this->packages(),
            'carousel'=>$this->slides(),
        ]]);
    }

    public function home()
    {
        return response()->json(['ok'=>true, 'data'=>[
            'carousel'=>$this->slides(),
            'categories'=>DB::table('tour_categories')->orderBy('name')->get(),
            'packages'=>$this->packages(),
            'languages'=>['es','en','fr','pt','de'],
        ]]);
    }

    public function carousel()
    {
        return response()->json(['ok'=>true, 'data'=>$this->slides()]);
    }

    public function package(int $id)
    {
        $package = $this->packages()->firstWhere('id', $id);

        return $package
            ? response()->json(['ok'=>true, 'data'=>$package])
            : response()->json(['ok'=>false, 'message'=>'Paquete no encontrado.'], 404);
    }

    public function bookingForm()
    {
        return response()->json(['ok'=>true, 'data'=>[
            'packages'=>$this->packages(),
            'languages'=>['es','en','fr','pt','de'],
            'apparel_sizes'=>['XS','S','M','L','XL','XXL'],
        ]]);
    }

    public function qrForm(string $token)
    {
        $branch = DB::table('branches')->where('qr_token', $token)->where('active', true)->first();

        if (!$branch) {
            return response()->json(['ok'=>false, 'message'=>'El código QR no es válido o la sucursal está inactiva.'], 404);
        }

        unset($branch->qr_token);

        return response()->json(['ok'=>true, 'data'=>[
            'branch'=>$branch,
            'packages'=>$this->packages(),
            'languages'=>['es','en','fr','pt','de'],
            'apparel_sizes'=>['XS','S','M','L','XL','XXL'],
        ]]);
    }

    public function onlineReservation(Request $request, TourismController $tourism)
    {
        $request->merge(['source'=>'online']);
        return $tourism->storeTourist($request);
    }

    public function qrReservation(Request $request, string $token, TourismController $tourism)
    {
        $branch = DB::table('branches')->where('qr_token', $token)->where('active', true)->first();
        if (!$branch) {
            return response()->json(['ok'=>false, 'message'=>'El código QR no es válido o la sucursal está inactiva.'], 404);
        }

        $request->merge(['source'=>'qr', 'branch_token'=>$token]);
        return $tourism->storeTourist($request);
    }

    private function packages()
    {
        return DB::table('tour_packages as p')
            ->join('tour_categories as c', 'c.id', '=', 'p.tour_category_id')
            ->where('p.active', true)
            ->select('p.*', 'c.name as category_name', 'c.color as category_color')
            ->orderBy('p.name')
            ->get()
            ->each(function ($package) {
                $package->image_url = $package->image_path ? url(ltrim($package->image_path, '/')) : null;
            });
    }

    private function slides()
    {
        return DB::table('home_slides')
            ->where('active', true)
            ->select('id','title','subtitle','image_path','display_order','active','updated_at')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->each(function ($slide) {
                $slide->image_url = url(ltrim($slide->image_path, '/'));
            });
    }
}
