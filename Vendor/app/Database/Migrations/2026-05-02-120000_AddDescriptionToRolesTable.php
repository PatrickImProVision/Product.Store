<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDescriptionToRolesTable extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('roles')) {
            return;
        }

        if ($this->db->fieldExists('description', 'roles')) {
            return;
        }

        $this->db->query('ALTER TABLE roles ADD COLUMN description TEXT NULL AFTER name');
    }

    public function down(): void
    {
        if (! $this->db->tableExists('roles') || ! $this->db->fieldExists('description', 'roles')) {
            return;
        }

        $this->forge->dropColumn('roles', 'description');
    }
}
