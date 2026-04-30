<?php

namespace App\Models;

use CodeIgniter\Model;

class WebSettingModel extends Model
{
    protected $table            = 'web_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['title', 'description'];
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
}
