<?php

namespace App\Models;

use CodeIgniter\Model;

class RolesModel extends Model
{
    public const SLUG_USER           = 'user';
    public const SLUG_ADMINISTRATOR = 'administrator';

    protected $table         = 'roles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['slug', 'name'];

    public function slugForRoleId(int $roleId): string
    {
        $row = $this->find($roleId);

        return $row !== null ? (string) $row['slug'] : self::SLUG_USER;
    }

    public function nameForRoleId(int $roleId): string
    {
        $row = $this->find($roleId);

        return $row !== null ? (string) $row['name'] : 'User';
    }

    public function idForSlug(string $slug): ?int
    {
        $row = $this->where('slug', $slug)->first();

        return $row !== null ? (int) $row['id'] : null;
    }
}
