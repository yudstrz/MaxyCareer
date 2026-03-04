<?php
$html = file_get_contents('kitalulus_out.html');

$jobs = [];
preg_match_all('/href="(\/lowongan\/[^"]+)"/', $html, $links);
$uniqueLinks = array_values(array_unique(array_filter($links[1], function ($l) {
    return !str_contains($l, '?') && strlen(explode('/', $l)[2] ?? '') > 3;

})));

preg_match_all('/<h3[^>]*>(.*?)<\/h3>/is', $html, $titles);
$titleref = $titles[1];

echo "Links: " . count($uniqueLinks) . "\n";
print_r(array_slice($uniqueLinks, 0, 5));

$limit = min(count($titleref), 10);
for ($i = 0; $i < $limit; $i++) {
    $link = isset($uniqueLinks[$i]) ? "https://kerja.kitalulus.com" . $uniqueLinks[$i] : "https://kerja.kitalulus.com/id/lowongan";
    $cleanTitle = html_entity_decode(strip_tags($titleref[$i]));
    echo "- $cleanTitle ($link)\n";
}
