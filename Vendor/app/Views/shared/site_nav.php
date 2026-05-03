<?php

use App\Libraries\MemberCapabilityGate;
use App\Models\RolesModel;

$memberNav      = session()->get('member_user');
$memberLoggedIn = is_array($memberNav) && ! empty($memberNav['id']);

$navMember = $memberLoggedIn ? $memberNav : null;

$storeBrowseOk = $navMember === null
    || MemberCapabilityGate::bypasses($navMember)
    || ! MemberCapabilityGate::enforcementActive($navMember)
    || MemberCapabilityGate::allows($navMember, 'cap_store_view');

$storeManageProductsOk = $navMember !== null && (
    MemberCapabilityGate::bypasses($navMember)
    || ! MemberCapabilityGate::enforcementActive($navMember)
    || MemberCapabilityGate::allows($navMember, 'cap_store_products_cud')
);

$memberProfileOk = $navMember !== null && (
    MemberCapabilityGate::bypasses($navMember)
    || ! MemberCapabilityGate::enforcementActive($navMember)
    || MemberCapabilityGate::allows($navMember, 'cap_member_profile')
);

$dashboardCapsOk = $navMember !== null && (
    MemberCapabilityGate::bypasses($navMember)
    || ! MemberCapabilityGate::enforcementActive($navMember)
    || MemberCapabilityGate::allows($navMember, 'cap_dashboard')
);

$memberHasDashboardAccess = $memberLoggedIn
    && RolesModel::slugMayUseDashboard((string) ($memberNav['role'] ?? ''))
    && $dashboardCapsOk;

$showStoreMenu = $storeBrowseOk || $storeManageProductsOk;

$accountNavLabel = 'Account';
$accountNavFull  = $accountNavLabel;

if ($memberLoggedIn && is_array($memberNav)) {
    $dn = trim((string) ($memberNav['display_name'] ?? ''));
    $em = trim((string) ($memberNav['email'] ?? ''));

    if ($dn !== '') {
        $accountNavFull = $dn;
    } elseif ($em !== '') {
        $accountNavFull = $em;
    } else {
        $accountNavFull = 'Account';
    }

    $accountNavLabel = $accountNavFull;
    if (mb_strlen($accountNavLabel) > 26) {
        $accountNavLabel = mb_substr($accountNavLabel, 0, 23) . '…';
    }
}
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-semibold text-dark" href="<?= site_url('Index') ?>"><?= esc($webTitle ?? 'Product Store') ?></a>
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <?php if ($showStoreMenu): ?>
                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle text-secondary"
                            href="#"
                            id="navStoreDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            Store
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navStoreDropdown">
                            <?php if ($storeBrowseOk): ?>
                                <li><a class="dropdown-item" href="<?= site_url('Store/Index') ?>">Products</a></li>
                                <li><a class="dropdown-item" href="<?= site_url('Store/Search/Index') ?>">Search</a></li>
                            <?php endif; ?>
                            <?php if ($storeBrowseOk && $storeManageProductsOk): ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <?php if ($storeManageProductsOk): ?>
                                <li><a class="dropdown-item" href="<?= site_url('Store/Product/Create') ?>">Add product</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle text-secondary text-truncate"
                        style="max-width: 14rem;"
                        href="#"
                        id="navAccountDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        <?php if ($memberLoggedIn && $accountNavFull !== ''): ?>
                            title="<?= esc($accountNavFull) ?>"
                            aria-label="<?= esc('Account menu for ' . $accountNavFull) ?>"
                        <?php else: ?>
                            aria-label="Account menu"
                        <?php endif; ?>
                    >
                        <?= esc($accountNavLabel) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navAccountDropdown">
                        <?php if (! $memberLoggedIn): ?>
                            <li><a class="dropdown-item" href="<?= site_url('Member/User/Register') ?>">Register</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('Member/User/Login') ?>">Login</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <?php if (! $memberHasDashboardAccess): ?>
                            <li><a class="dropdown-item" href="<?= site_url('Member/Admin/Login') ?>">Administrator sign in</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <?php if (! $memberLoggedIn || $memberProfileOk): ?>
                            <li><a class="dropdown-item" href="<?= site_url('Member/User/Profile') ?>">Profile</a></li>
                        <?php endif; ?>
                        <?php if ($memberLoggedIn): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= site_url('Member/User/Logout') ?>">Sign out</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

                <?php if ($memberHasDashboardAccess): ?>
                    <li class="nav-item dropdown">
                        <a
                            class="nav-link dropdown-toggle text-secondary"
                            href="#"
                            id="navDashDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                        >
                            Dashboard
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navDashDropdown">
                            <li><a class="dropdown-item" href="<?= site_url('DashBoard/Index') ?>">Overview</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('DashBoard/Member/Admin/Roles') ?>">Roles</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('DashBoard/Member/User/Profiles') ?>">Member profiles</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= site_url('DashBoard/Site_Contacts') ?>">Site contacts</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= site_url('DashBoard/SEO_Settings') ?>">SEO settings</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('DashBoard/Web_Settings') ?>">Web settings</a></li>
                            <li><a class="dropdown-item" href="<?= site_url('DashBoard/Web_Promoting') ?>">Promote</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
