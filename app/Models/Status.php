<?php

namespace App\Models;

use App\Traits\TranslateTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory, TranslateTrait;

    protected $table = 'tb_status';

    protected $guarded = [];


    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }


    public function services()
    {
        return $this->hasMany(Service::class, 'status_id');
    }



}
