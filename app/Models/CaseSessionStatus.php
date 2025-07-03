<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseSessionStatus extends Model
{
    use HasFactory, TranslateTrait;
    protected $table = 'cases_session_statuses';

    protected $guarded = [];



}
