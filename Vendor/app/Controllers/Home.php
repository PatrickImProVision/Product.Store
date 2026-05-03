<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $layout     = $this->getSiteLayoutData();
        $promotions = [];

        // Promotions: avoid fieldExists() (schema cache / driver edge cases can break the whole query).
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('web_promoting')) {
                try {
                    $promotions = $db->table('web_promoting')
                        ->groupStart()
                        ->where('is_active', 1)
                        ->orWhere('is_active', null)
                        ->groupEnd()
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();
                } catch (\Throwable $e) {
                    $promotions = $db->table('web_promoting')
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();

                    $promotions = array_values(array_filter(
                        $promotions,
                        static function (array $row): bool {
                            if (! array_key_exists('is_active', $row)) {
                                return true;
                            }
                            if ($row['is_active'] === null) {
                                return true;
                            }

                            return (int) $row['is_active'] === 1;
                        }
                    ));
                }
            }
        } catch (\Throwable $e) {
            $promotions = [];
        }

        $siteContacts = [];
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('site_contacts')) {
                $siteContacts = $db->table('site_contacts')
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();
            }
        } catch (\Throwable $e) {
            $siteContacts = [];
        }

        $defaultPromotions = [
            [
                'title'       => 'Web Promote',
                'description' => 'Promote your products with powerful search, basket flow, and a clean storefront experience.',
            ],
            [
                'title'       => 'Grow Faster',
                'description' => 'Highlight launches, seasonal collections, or featured categories — add more blocks in DashBoard → Promote.',
            ],
        ];

        // Only when there are no DB rows: show two placeholder cards (two-across layout).
        if ($promotions === []) {
            $promotions = $defaultPromotions;
        }

        return view('welcome_message', [
            'metaTitle'       => $layout['metaTitle'],
            'metaDescription' => $layout['metaDescription'],
            'metaKeywords'    => $layout['metaKeywords'],
            'webTitle'        => $layout['webTitle'],
            'webDescription'  => $layout['webDescription'],
            'bodyClass'       => static::STOREFRONT_BODY_CLASS,
            'pageTitle'       => 'Home',
            'promotions'      => $promotions,
            'siteContacts'    => $siteContacts,
        ]);
    }
}
