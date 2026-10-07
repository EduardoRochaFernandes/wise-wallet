<?php
/**
 * Renders SEO meta tags from a $seo array:
 *   title, description, url, type, image, noindex(bool), jsonld(array|null)
 */
$seo = array_merge([
    'title'       => WW_NAME . ' — Smart personal finance',
    'description' => 'WiseWallet: record, understand, plan, simulate and learn about your money in one place.',
    'url'         => WW_URL . ($_SERVER['REQUEST_URI'] ?? '/'),
    'type'        => 'website',
    'image'       => WW_URL . '/assets/img/og-cover.svg',
    'noindex'     => false,
    'jsonld'      => null,
], $seo ?? []);
$canonical = strtok($seo['url'], '?');
?>
<title><?= e($seo['title']) ?></title>
<meta name="description" content="<?= e($seo['description']) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($seo['noindex']): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large">
<?php endif; ?>
<meta property="og:type" content="<?= e($seo['type']) ?>">
<meta property="og:site_name" content="<?= e(WW_NAME) ?>">
<meta property="og:title" content="<?= e($seo['title']) ?>">
<meta property="og:description" content="<?= e($seo['description']) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($seo['image']) ?>">
<meta property="og:locale" content="pt_PT">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seo['title']) ?>">
<meta name="twitter:description" content="<?= e($seo['description']) ?>">
<meta name="twitter:image" content="<?= e($seo['image']) ?>">
<?php if (!empty($seo['jsonld'])): ?>
<script type="application/ld+json" <?= nonce_attr() ?>><?= json_encode($seo['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
