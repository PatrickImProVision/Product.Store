</main>
<?php
$chrome = [
    'documentTitle'   => $documentTitle ?? '',
    'pageTitle'       => $pageTitle ?? '',
    'metaTitle'       => $metaTitle ?? 'Product Store',
    'metaDescription' => $metaDescription ?? '',
    'metaKeywords'    => $metaKeywords ?? '',
    'webTitle'        => $webTitle ?? 'Product Store',
];
?>
<?= view('shared/site_footer', $chrome) ?>
