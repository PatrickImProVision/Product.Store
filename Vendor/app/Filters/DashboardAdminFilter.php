<?php

namespace App\Filters;

use App\Libraries\MemberCapabilityGate;
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
                'Please sign in as Owner or Administrator to access the dashboard.'
            );
        }

        $role = (string) ($member['role'] ?? RolesModel::SLUG_USER);
        if (! RolesModel::slugMayUseDashboard($role)) {
            return redirect()->to(site_url('Index'))->with(
                'message',
                'You do not have permission to access the dashboard.'
            );
        }

        if (
            MemberCapabilityGate::enforcementActive($member)
            && ! MemberCapabilityGate::bypasses($member)
            && ! MemberCapabilityGate::allows($member, 'cap_dashboard')
        ) {
            return redirect()->to(site_url('Index'))->with(
                'message',
                'Your role does not include dashboard access in its capability outline.'
            );
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
