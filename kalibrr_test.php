<?php
$html = file_get_contents('kalibrr_out.html');
preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/is', $html, $next);
$json = json_decode($next[1], true);
$jobs = $json['props']['pageProps']['jobs'] ?? [];
if (!empty($jobs)) {
    foreach (array_slice($jobs, 0, 5) as $job) {
        echo "Title: " . $job['name'] . "\n";
        echo "Company: " . ($job['company']['name'] ?? 'Unknown Company') . "\n";
        echo "Link: https://www.kalibrr.com/c/" . ($job['company']['code'] ?? 'unknown') . "/jobs/" . $job['id'] . "/" . ($job['slug'] ?? 'job') . "\n\n";
    }
}
