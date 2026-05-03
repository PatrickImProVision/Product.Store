<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use Config\CiTables;

class AddDescriptionToRolesTable extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists(CiTables::USER_ROLES)) {
            return;
        }

        if ($this->db->fieldExists('description', CiTables::USER_ROLES)) {
            return;
        }

        $this->db->query('ALTER TABLE `' . $this->db->prefixTable(CiTables::USER_ROLES) . '` ADD COLUMN description TEXT NULL AFTER name');
    }

    public function down(): void
    {
        if (! $this->db->tableExists(CiTables::USER_ROLES) || ! $this->db->fieldExists('description', CiTables::USER_ROLES)) {
            return;
        }

        $this->forge->dropColumn(CiTables::USER_ROLES, 'description');
    }
}
