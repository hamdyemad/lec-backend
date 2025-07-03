<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseModel extends Model
{
    use HasFactory, TranslateTrait;
    protected $table = 'cases';

    protected $guarded = [];

    public function client() {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function lawyer() {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    public function city() {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function invoice() {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function case_status() {
        return $this->belongsTo(CaseStatus::class, 'case_status_id');
    }

    public function case_orders() {
        return $this->hasMany(CaseLawyerOrder::class, 'case_id');
    }

    public function sessions() {
        return $this->hasMany(CaseSession::class, 'case_id');
    }


}
