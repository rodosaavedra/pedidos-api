<?php
namespace App\Models\Vargas;

use Illuminate\Database\Eloquent\Model;

class Local extends Model
{
    protected $connection = 'vargas';

    protected $table = 'locales';

    public $timestamps = false;
}