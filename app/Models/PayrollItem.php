<?php

namespace App\Models;

use Database\Factories\PayrollItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    /** @use HasFactory<PayrollItemFactory> */
    use HasFactory;
}
