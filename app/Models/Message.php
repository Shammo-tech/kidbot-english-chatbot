<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Message extends Model {
    protected $guarded = [];
    public function child() { return $this->belongsTo(Child::class); }
}