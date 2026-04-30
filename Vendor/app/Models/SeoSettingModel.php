<?php

namespace App\Models;

use CodeIgniter\Model;

class SeoSettingModel extends Model
{
    protected $table            = 'seo_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['meta_title', 'meta_description', 'meta_keywords'];
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
}
