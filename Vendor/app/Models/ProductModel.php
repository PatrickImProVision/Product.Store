<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\CiTables;

class ProductModel extends Model
{
    protected $table            = CiTables::PRODUCTS;
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['name', 'price', 'quantity', 'description', 'remote_image', 'user_id'];
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
}
