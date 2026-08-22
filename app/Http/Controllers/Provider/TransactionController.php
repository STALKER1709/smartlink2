<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->where('payer_id', $request->user()->id)
            ->with('plan')
            ->latest('created_at')
            ->paginate(15);

        return view('provider.transactions.index', [
            'payments' => $payments,
        ]);
    }
}
