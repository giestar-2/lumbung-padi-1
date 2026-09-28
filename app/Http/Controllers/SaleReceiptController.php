<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\View\View;

class SaleReceiptController extends Controller
{
    public function __invoke(Sale $sale): View
    {
        return view('livewire.sales.receipt', ['sale' => $sale->load('customer', 'items', 'payments')]);
    }
}
