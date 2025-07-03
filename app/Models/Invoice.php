<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, TranslateTrait;
    protected $table = 'invoices';

    protected $guarded = [];


    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }


    public function service() {
        return $this->belongsTo(Service::class);
    }

    public function account() {
        return $this->belongsTo(Account::class);
    }

}
