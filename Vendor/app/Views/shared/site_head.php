<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(\App\Libraries\SiteChrome::browserTitle([
        'webTitle'       => $webTitle ?? null,
        'metaTitle'      => $metaTitle ?? null,
        'pageTitle'      => $pageTitle ?? null,
        'documentTitle'  => $documentTitle ?? null,
    ])) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? 'Product Store powered by CodeIgniter') ?>">
    <meta name="keywords" content="<?= esc($metaKeywords ?? '') ?>">
    <link rel="shortcut icon" type="image/png" href="/favicon.ico">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
</head>
<body class="<?= esc($bodyClass ?? 'bg-light') ?>">
