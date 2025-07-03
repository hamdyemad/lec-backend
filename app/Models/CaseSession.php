<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseSession extends Model
{
    use HasFactory, TranslateTrait;
    protected $table = 'cases_sessions';

    protected $guarded = [];

    public function case() {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function status() {
        return $this->belongsTo(CaseSessionStatus::class, 'status_id');
    }


    public function histories() {
        return $this->hasMany(CaseSessionHistory::class, 'case_session_id');
    }


}
