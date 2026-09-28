<?php

namespace App\Models;

use Database\Factories\CashEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashEntry extends Model
{
    /** @use HasFactory<CashEntryFactory> */
    use HasFactory;
}
