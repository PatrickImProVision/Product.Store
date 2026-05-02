<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ('Dashboard — ' . ($webTitle ?? 'Product Store')),
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? '',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
?>
<?= view('shared/site_head', $chrome) ?>
<?= view('shared/site_nav', $chrome) ?>

<main class="container py-5">
