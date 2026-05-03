<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\CiTables;

class WebSettingModel extends Model
{
    protected $table            = CiTables::WEB_SETTINGS;
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['title', 'description'];
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
}
