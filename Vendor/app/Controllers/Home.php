<?php

namespace App\Controllers;

use App\Models\SeoSettingModel;
use App\Models\WebSettingModel;

class Home extends BaseController
{
    public function index(): string
    {
        $seo = null;
        $web = null;

        // Never let SEO storage issues break the home page.
        try {
            $db = \Config\Database::connect();

            if ($db->tableExists('seo_settings')) {
                $seoModel = new SeoSettingModel();
                $seo      = $seoModel->first();
            }

            if ($db->tableExists('web_settings')) {
                $webModel = new WebSettingModel();
                $web      = $webModel->first();
            }
        } catch (\Throwable $e) {
            $seo = null;
            $web = null;
        }

        $siteName = trim((string) ($web['title'] ?? '')) !== '' ? $web['title'] : 'Product Store';

        return view('welcome_message', [
            'metaTitle'       => $seo['meta_title'] ?? $siteName,
            'metaDescription' => $seo['meta_description'] ?? ($siteName . ' powered by CodeIgniter'),
            'metaKeywords'    => $seo['meta_keywords'] ?? '',
            'webTitle'        => $siteName,
            'webDescription'  => $web['description'] ?? 'This project now uses Bootstrap styling instead of the default CodeIgniter welcome style.',
        ]);
    }
}
