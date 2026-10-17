<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Child extends Model {
    protected $table = 'children';
    protected $guarded = [];
    public function messages() { return $this->hasMany(Message::class); }
}