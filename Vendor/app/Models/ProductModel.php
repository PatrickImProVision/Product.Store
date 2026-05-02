<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['name', 'price', 'quantity', 'description', 'remote_image', 'user_id'];
    protected $useAutoIncrement = true;
    protected $protectFields    = true;
}
