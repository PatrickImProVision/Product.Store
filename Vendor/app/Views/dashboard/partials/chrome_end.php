</main>
<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? ('Dashboard — ' . ($webTitle ?? 'Product Store')),
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? '',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
?>
<?= view('shared/site_footer', $chrome) ?>
