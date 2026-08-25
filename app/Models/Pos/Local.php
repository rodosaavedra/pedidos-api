<?php
namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class Local extends Model
{
    protected $connection = 'Pos';

    protected $table = 'locales';

    public $timestamps = false;
}