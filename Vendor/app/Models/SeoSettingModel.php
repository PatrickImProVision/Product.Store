<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\CiTables;

class SeoSettingModel extends Model
{
    protected $table            = CiTables::SEO_SETTINGS;
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['meta_title', 'meta_description', 'meta_keywords'];
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
}
