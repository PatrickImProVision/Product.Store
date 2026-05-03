<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\MemberCapabilityGate;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Requires ROLE_CAPS_JSON allowlist caps for Store/* and Member/User/* when enforcement is active.
 */
class MemberCapabilityFilter implements FilterInterface
{
    /**
     * @return list<string>|null null = route not gated by capabilities
     */
    private function requiredCaps(array $segments): ?array
    {
        if ($segments === []) {
            return null;
        }

        $norm = static function (array $segs): array {
            return array_map(static fn ($s) => strtolower((string) $s), $segs);
        };

        $segments = $norm($segments);

        $s0 = $segments[0] ?? '';

        if ($s0 === 'store') {
            $s1 = $segments[1] ?? '';

            if ($s1 === 'product') {
                $op = $segments[2] ?? '';

                return match ($op) {
                    'create', 'edit', 'delete' => ['cap_store_products_cud'],
                    'view' => ['cap_store_view'],
                    default => ['cap_store_view'],
                };
            }

            if ($s1 === 'basket') {
                return ['cap_store_basket_checkout'];
            }

            if ($s1 === 'checkout') {
                return ['cap_store_basket_checkout'];
            }

            if ($s1 === 'search') {
                return ['cap_store_view'];
            }

            if ($s1 === 'index') {
                return ['cap_store_view'];
            }

            return ['cap_store_view'];
        }

        if ($s0 === 'member' && ($segments[1] ?? '') === 'user') {
            $page = $segments[2] ?? '';

            if ($page === 'logout') {
                return null;
            }

            if (in_array($page, ['profile', 'edit', 'deactivaterequest', 'deactivate', 'delete'], true)) {
                return ['cap_member_profile'];
            }

            if (in_array($page, ['register', 'login', 'forgotpassword', 'activate'], true)) {
                return ['cap_member_register_login'];
            }

            return null;
        }

        return null;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $member = session()->get('member_user');
        if (! is_array($member) || empty($member['id'])) {
            return null;
        }

        if (MemberCapabilityGate::bypasses($member)) {
            return null;
        }

        if (! MemberCapabilityGate::enforcementActive($member)) {
            return null;
        }

        $segments = $request->getUri()->getSegments();
        $needed     = $this->requiredCaps($segments);
        if ($needed === null) {
            return null;
        }

        foreach ($needed as $cap) {
            if (! MemberCapabilityGate::allows($member, $cap)) {
                return redirect()->to(site_url('Index'))->with(
                    'message',
                    'Your role does not include access to that area. Contact an administrator if you need it.'
                );
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
