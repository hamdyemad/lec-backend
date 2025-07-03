<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseSessionHistory extends Model
{
    use HasFactory, TranslateTrait;
    protected $table = 'cases_sessions_history';

    protected $guarded = [];

    public function case_session() {
        return $this->belongsTo(CaseSession::class, 'case_session_id');
    }

    public function status() {
        return $this->belongsTo(CaseSessionStatus::class, 'status_id');
    }



}
