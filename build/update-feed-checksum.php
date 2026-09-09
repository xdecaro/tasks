<?php
$root = dirname(__DIR__);
$version = trim((string) file_get_contents($root . '/VERSION'));
$package = $root . '/dist/pkg_xdecarotasks_' . $version . '.zip';
$feedFile = $root . '/updates/pkg_xdecarotasks.xml';
if (!is_file($package) || !is_file($feedFile)) { fwrite(STDERR, "Missing package or update feed.\n"); exit(1); }
$sha = hash_file('sha256', $package);
$xml = file_get_contents($feedFile);
$xml = preg_replace('/<sha256>[a-f0-9]{64}<\/sha256>/', '<sha256>' . $sha . '</sha256>', $xml, 1, $count);
if ($count !== 1) { fwrite(STDERR, "Could not update SHA-256.\n"); exit(1); }
file_put_contents($feedFile, $xml);
echo $sha . PHP_EOL;
