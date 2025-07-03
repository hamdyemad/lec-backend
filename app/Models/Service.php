<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $table = 'services';

    protected $guarded = [];


    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function client() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function status() {
        return $this->belongsTo(Status::class);
    }

    public function country() {
        return $this->belongsTo(Country::class);
    }

    public function city() {
        return $this->belongsTo(City::class);
    }

    public function invoices() {
        return $this->hasMany(Invoice::class, 'service_id');
    }

    public function case_type() {
        return $this->belongsTo(CaseType::class, 'case_type_id');
    }

    public function case() {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }


    public function paid_invoice() {
        return $this->belongsTo(Invoice::class, 'invoice_id')->where('status', 'paid');
    }




}
