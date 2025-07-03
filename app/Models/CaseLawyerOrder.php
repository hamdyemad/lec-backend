<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseLawyerOrder extends Model
{
    use HasFactory, TranslateTrait;
    protected $table = 'case_lawyer_orders';

    protected $guarded = [];



    public function lawyer() {
        return $this->belongsTo(User::class, 'lawyer_id');
    }

    public function case() {
        return $this->belongsTo(CaseModel::class);
    }

}
