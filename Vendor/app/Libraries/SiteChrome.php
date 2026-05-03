<?php

declare(strict_types=1);

namespace App\Libraries;

final class SiteChrome
{
    /**
     * Browser tab title: "{siteTitle} - {pageLabel}", or site-only when no page label.
     *
     * Supports legacy {@see $documentTitle} values ending with " — {siteTitle}" or " - {siteTitle}".
     *
     * @param array{webTitle?: string|null, metaTitle?: string|null, pageTitle?: string|null, documentTitle?: string|null} $data
     */
    public static function browserTitle(array $data): string
    {
        $siteTitle = trim((string) ($data['webTitle'] ?? ''));
        if ($siteTitle === '') {
            $siteTitle = trim((string) ($data['metaTitle'] ?? ''));
        }
        if ($siteTitle === '') {
            $siteTitle = 'Product Store';
        }

        $pageLabel = trim((string) ($data['pageTitle'] ?? ''));
        $docFull   = trim((string) ($data['documentTitle'] ?? ''));

        $legacyEm = ' — ' . $siteTitle;
        $legacyHy = ' - ' . $siteTitle;

        if ($pageLabel === '' && $docFull !== '') {
            if (str_ends_with($docFull, $legacyEm)) {
                $pageLabel = trim(substr($docFull, 0, -strlen($legacyEm)));
            } elseif (str_ends_with($docFull, $legacyHy)) {
                $pageLabel = trim(substr($docFull, 0, -strlen($legacyHy)));
            }
        }

        if ($pageLabel !== '') {
            return $siteTitle . ' - ' . $pageLabel;
        }

        if ($docFull !== '') {
            $meta = trim((string) ($data['metaTitle'] ?? ''));
            if ($docFull === $siteTitle || ($meta !== '' && $docFull === $meta)) {
                return $siteTitle;
            }

            return $siteTitle . ' - ' . $docFull;
        }

        return $siteTitle;
    }
}
