<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\User;
use App\Models\Group;
use App\Models\Stock;
use App\Models\Billing;
use App\Models\Payment;
use App\Models\Release;
use App\Models\Storage;
use App\Models\Incident;
use App\Models\Temperature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function welcome() 
    {
        return view('welcome');
    }

    public function dashboard() 
    {
        $stocks = Stock::with('customer', 'storage');
        $payments = Payment::with('customer', 'stock');
        $billings = Billing::with('customer', 'stock');
        $customers = User::where('group_id', '>', 4);
        $temperatures = Temperature::where('created_at', now()->toDateString());

        if(auth()->user()->group_id > 4) {
            $stocks = $stocks->where('customer_id', auth()->id());
            $payments = $payments->where('customer_id', auth()->id());
            $customers = $customers->where('id', auth()->id());
            $billings = $billings->where('customer_id', auth()->id());
            $userId = auth()->id();
            $temperatures = $temperatures->whereIn('storage_id', function ($query) use ($userId) {
                $query->select('storage_id')->from('stocks')->where('customer_id', $userId);
            });
        }

        $storages = Storage::all();
        $releases = Release::all();
        $payments = $payments->get();
        $customers = $customers->get();
        $temperatures = $temperatures->orderBy('created_at', 'desc')->get();
        $stocks = $stocks->orderByDesc('id')->get()->take(10);
        $billings = $billings->orderByDesc('id')->get()->take(5);
        $incidents = Incident::where('status', 'EN COURS')->orderByDesc('id')->get()->take(3);
        return view('dashboard', compact('stocks', 'payments', 'temperatures', 'incidents', 'customers', 'storages', 'releases', 'billings'));
    }

    public function getDashboardContent(Request $request) 
    {
        $stocks = Stock::with('customer', 'storage');
        $customers = User::where('group_id', '>', 4);
        $payments = Payment::with('customer', 'stock');
        $billings = Billing::with('customer', 'stock');
        $releases = Release::with('stock');
        $temperatures = Temperature::with('storage');

        if($request->has('storage_id')) {
            $stocks = $stocks->where('storage_id', $request->storage_id);

            $billings = $billings->whereIn('stock_id', $stocks->pluck('id'));
            $payments = $payments->whereIn('stock_id', $stocks->pluck('id'));
            $temperatures = $temperatures->where('storage_id', $request->storage_id);
        }

        if(auth()->user()->group_id > 4) {
            $customers = $customers->where('id', auth()->id());
            $stocks = $stocks->where('customer_id', auth()->id());
            $billings = $billings->where('customer_id', auth()->id());
            $payments = $payments->where('customer_id', auth()->id());

            $userId = auth()->id();
            $temperatures = Temperature::whereIn('storage_id', function ($query) use ($userId) {
                $query->select('storage_id')->from('stocks')->where('customer_id', $userId);
            })->whereDate('created_at', now()->toDateString());
        } else if($request->has('customer_id')) {
            $customers = $customers->where('id', $request->customer_id);
            $stocks = $stocks->where('customer_id', $request->customer_id);
            $billings = $billings->where('customer_id', $request->customer_id);
            $payments = $payments->where('customer_id', $request->customer_id);

            $userId = $request->customer_id;
            $temperatures = $temperatures->whereIn('storage_id', function ($query) use ($userId) {
                $query->select('storage_id')->from('stocks')->where('customer_id', $userId);
            })->whereDate('created_at', now()->toDateString());
        }

        $releases = $releases->get();
        $payments = $payments->get();
        $stocks = $stocks->orderByDesc('id')->get()->take(10);
        $billings = $billings->orderByDesc('id')->get()->take(5);
        $temperatures = $temperatures->orderByDesc('created_at')->get();
        $releases = $releases->whereIn('stock_id', $stocks->pluck('id'));
        $incidents = Incident::where('status', 'EN COURS')->orderByDesc('id')->get()->take(3);
        return view('components.dashboard', compact('stocks', 'payments', 'temperatures', 'incidents', 'billings', 'releases'));
    }

    public function profile() 
    {
        return view('profile');
    }

    public function setLocaleUpdate($locale) 
    {
        session(['locale' => $locale]);

        if (auth()->check()) {
            $currentThis = User::find(auth()->id());
            $currentThis?->update(compact('locale'));
        }

        return back();
    }

    public function groups() 
    {
        $groups = Group::where('id', '<=', 4)->get();
        return view('admin.groups', compact('groups'));
    }

    public function cities() 
    {
        $cities = City::all();
        return view('admin.cities', compact('cities'));
    }
}
