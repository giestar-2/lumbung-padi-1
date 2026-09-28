<?php

namespace App\Models;

use Database\Factories\BatchStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchStage extends Model
{
    /** @use HasFactory<BatchStageFactory> */
    use HasFactory;

    protected $guarded = [];
}
