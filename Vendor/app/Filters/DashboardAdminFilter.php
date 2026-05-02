<?php

namespace App\Filters;

use App\Libraries\RolesSchema;
use App\Models\RolesModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        RolesSchema::ensure();

        $member = session()->get('member_user');
        if (! is_array($member) || empty($member['id'])) {
            return redirect()->to(site_url('Member/Admin/Login'))->with(
                'message',
                'Please sign in as an administrator to access the dashboard.'
            );
        }

        $role = (string) ($member['role'] ?? RolesModel::SLUG_USER);
        if ($role !== RolesModel::SLUG_ADMINISTRATOR) {
            return redirect()->to(site_url('Index'))->with(
                'message',
                'You do not have permission to access the dashboard.'
            );
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
