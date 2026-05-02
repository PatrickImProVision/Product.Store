<?php

namespace App\Controllers;

class DashBoard extends BaseController
{
    /**
     * Site chrome (nav + footer), no hero image. Pass `documentTitle` in `$data`.
     *
     * @param array<string, mixed> $data
     */
    private function renderDashboard(string $view, array $data): string
    {
        return view($view, array_merge($this->getSiteLayoutData(), $data));
    }

    private function ensureWebPromotingTable(): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->query(
                'CREATE TABLE IF NOT EXISTS web_promoting (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL DEFAULT "",
                    description TEXT NULL,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );
            $this->upgradeWebPromotingSchema();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function upgradeWebPromotingSchema(): void
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('web_promoting')) {
                return;
            }

            if (! $db->fieldExists('sort_order', 'web_promoting')) {
                $db->query('ALTER TABLE web_promoting ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER description');
            }

            if (! $db->fieldExists('is_active', 'web_promoting')) {
                $db->query('ALTER TABLE web_promoting ADD COLUMN is_active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER sort_order');
            }
        } catch (\Throwable $e) {
            // Best-effort upgrades only.
        }
    }

    private function ensureSiteContactTable(): bool
    {
        try {
            $db = \Config\Database::connect();
            $db->query(
                'CREATE TABLE IF NOT EXISTS site_contacts (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    message TEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
            );
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index()
    {
        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/dashboard_index', [
            'documentTitle' => 'Dashboard — ' . $layout['webTitle'],
            'notice'        => session()->getFlashdata('message'),
            'pageTitle'     => 'Dashboard',
            'pageMessage'   => 'Default system status page.',
        ]);
    }

    public function Site_Contacts()
    {
        if (! $this->ensureSiteContactTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $rows = $db->table('site_contacts')->orderBy('id', 'DESC')->get()->getResultArray();
        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/site_contacts_index', [
            'documentTitle' => 'Site contacts — ' . $layout['webTitle'],
            'rows'          => $rows,
            'message'       => session()->getFlashdata('message'),
        ]);
    }

    public function Site_Contact_Create()
    {
        if (! $this->ensureSiteContactTable()) {
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        if ($this->request->is('post')) {
            $db = \Config\Database::connect();
            $db->table('site_contacts')->insert([
                'name' => trim((string) $this->request->getPost('name')),
                'email' => trim((string) $this->request->getPost('email')),
                'message' => trim((string) $this->request->getPost('message')),
            ]);
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Contact created successfully.');
        }

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/site_contact_form', [
            'documentTitle' => 'Create site contact — ' . $layout['webTitle'],
            'mode'          => 'create',
            'row'           => null,
            'action'        => site_url('DashBoard/Site_Contact/Create'),
        ]);
    }

    public function Site_Contact_Edit(int $id)
    {
        if (! $this->ensureSiteContactTable()) {
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $row = $db->table('site_contacts')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Contact not found.');
        }

        if ($this->request->is('post')) {
            $db->table('site_contacts')->where('id', $id)->update([
                'name' => trim((string) $this->request->getPost('name')),
                'email' => trim((string) $this->request->getPost('email')),
                'message' => trim((string) $this->request->getPost('message')),
            ]);
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Contact updated successfully.');
        }

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/site_contact_form', [
            'documentTitle' => 'Edit site contact — ' . $layout['webTitle'],
            'mode'          => 'edit',
            'row'           => $row,
            'action'        => site_url('DashBoard/Site_Contact/Edit/' . $id),
        ]);
    }

    public function Site_Contact_Delete(int $id)
    {
        if (! $this->ensureSiteContactTable()) {
            return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $db->table('site_contacts')->where('id', $id)->delete();
        return redirect()->to(site_url('DashBoard/Site_Contacts'))->with('message', 'Contact deleted successfully.');
    }

    public function Web_Promoting()
    {
        if (! $this->ensureWebPromotingTable()) {
            return redirect()->to(site_url('Index'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $rows = $db->table('web_promoting')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/web_promoting_index', [
            'documentTitle' => 'Web promoting — ' . $layout['webTitle'],
            'rows'          => $rows,
            'message'       => session()->getFlashdata('message'),
        ]);
    }

    public function Web_Promoting_Create()
    {
        if (! $this->ensureWebPromotingTable()) {
            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        if ($this->request->is('post')) {
            $db = \Config\Database::connect();

            $sortOrder = (int) $this->request->getPost('sort_order');
            $isActive  = (string) $this->request->getPost('is_active') === '1' ? 1 : 0;

            $db->table('web_promoting')->insert([
                'title'       => trim((string) $this->request->getPost('title')),
                'description' => trim((string) $this->request->getPost('description')),
                'sort_order'  => $sortOrder,
                'is_active'   => $isActive,
            ]);

            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Promotion created successfully.');
        }

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/web_promoting_form', [
            'documentTitle' => 'Create promotion — ' . $layout['webTitle'],
            'mode'          => 'create',
            'row'           => null,
            'action'        => site_url('DashBoard/Web_Promoting/Create'),
            'message'       => session()->getFlashdata('message'),
        ]);
    }

    public function Web_Promoting_Edit(int $id)
    {
        if (! $this->ensureWebPromotingTable()) {
            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $row = $db->table('web_promoting')->where('id', $id)->get()->getRowArray();
        if ($row === null) {
            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Promotion not found.');
        }

        if ($this->request->is('post')) {
            $sortOrder = (int) $this->request->getPost('sort_order');
            $isActive  = (string) $this->request->getPost('is_active') === '1' ? 1 : 0;

            $db->table('web_promoting')->where('id', $id)->update([
                'title'       => trim((string) $this->request->getPost('title')),
                'description' => trim((string) $this->request->getPost('description')),
                'sort_order'  => $sortOrder,
                'is_active'   => $isActive,
            ]);

            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Promotion updated successfully.');
        }

        $layout = $this->getSiteLayoutData();

        return $this->renderDashboard('dashboard/web_promoting_form', [
            'documentTitle' => 'Edit promotion — ' . $layout['webTitle'],
            'mode'          => 'edit',
            'row'           => $row,
            'action'        => site_url('DashBoard/Web_Promoting/Edit/' . $id),
            'message'       => session()->getFlashdata('message'),
        ]);
    }

    public function Web_Promoting_Delete(int $id)
    {
        if (! $this->ensureWebPromotingTable()) {
            return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Database connection failed. Please check DB settings.');
        }

        $db = \Config\Database::connect();
        $db->table('web_promoting')->where('id', $id)->delete();

        return redirect()->to(site_url('DashBoard/Web_Promoting'))->with('message', 'Promotion deleted successfully.');
    }
}
