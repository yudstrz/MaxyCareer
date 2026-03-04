<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class JobFeedController extends Controller
{
    /**
     * Fetch job listings from multiple sources, filtered by query keywords.
     */
    public function search(Request $request)
    {
        $query = strtolower($request->input('q', ''));
        if (empty($query)) {
            return response()->json(['jobs' => [], 'total' => 0]);
        }

        // Cache per query for 15 minutes to speed up repeated searches
        $cacheKey = 'job_feed_all_' . md5($query);
        $jobs = Cache::remember($cacheKey, 900, function () use ($query) {
            $allJobs = [];

            // Fetch all sources in parallel (bypass SSL verify for local dev)
            $responses = Http::pool(fn($pool) => [
            $pool->as('remoteok')->timeout(12)->withoutVerifying()->get('https://remoteok.com/remote-jobs.rss'),
            $pool->as('wwr')->timeout(12)->withoutVerifying()->get('https://weworkremotely.com/remote-jobs.rss'),
            $pool->as('kitalulus')->timeout(12)->withoutVerifying()
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'])
            ->get("https://kerja.kitalulus.com/id/lowongan?q=" . urlencode($query)),
            $pool->as('kalibrr')->timeout(12)->withoutVerifying()
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'])
            ->get("https://www.kalibrr.id/job-board/te/" . strtolower(urlencode($query)) . "/1"),
            $pool->as('linkedin')->timeout(12)->withoutVerifying()
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
            ->get("https://www.linkedin.com/jobs/search?keywords=" . urlencode($query) . "&location=Indonesia&f_TPR=r2592000")
            ]);

            // Parse RSS Feeds
            if (isset($responses['remoteok']) && $responses['remoteok']->ok()) {
                $allJobs = array_merge($allJobs, $this->parseRss($responses['remoteok']->body(), 'RemoteOK'));
            }
            if (isset($responses['wwr']) && $responses['wwr']->ok()) {
                $allJobs = array_merge($allJobs, $this->parseRss($responses['wwr']->body(), 'WeWorkRemotely'));
            }

            // Parse HTML Scrapes
            if (isset($responses['kitalulus']) && $responses['kitalulus']->ok()) {
                $allJobs = array_merge($allJobs, $this->parseKitaLulus($responses['kitalulus']->body()));
            }
            if (isset($responses['kalibrr']) && $responses['kalibrr']->ok()) {
                $allJobs = array_merge($allJobs, $this->parseKalibrr($responses['kalibrr']->body()));
            }
            if (isset($responses['linkedin']) && $responses['linkedin']->ok()) {
                $allJobs = array_merge($allJobs, $this->parseLinkedIn($responses['linkedin']->body()));
            }

            // Filter RSS by query keywords (HTML scrapes are already filtered by the URL query)
            $keywords = array_filter(explode(' ', $query), fn($w) => strlen($w) >= 3);

            $filteredJobs = array_values(array_filter($allJobs, function ($job) use ($keywords) {
                    // If it's not from an RSS feed, we keep it as the search engine already filtered it
                    if (!in_array($job['source'], ['RemoteOK', 'WeWorkRemotely']))
                        return true;

                    if (empty($keywords))
                        return true;

                    $text = strtolower($job['title'] . ' ' . $job['company']);
                    foreach ($keywords as $kw) {
                        if (str_contains($text, $kw))
                            return true;
                    }
                    return false;
                }
                ));

                // Shuffle the results slightly to mix sources, but keep it mostly deterministic
                usort($filteredJobs, function () {
                    return rand(-1, 1);
                }
                );
                return $filteredJobs;
            });

        return response()->json([
            'jobs' => array_slice($jobs, 0, 30),
            'total' => count($jobs),
            'searchLinks' => $this->generateSearchLinks($query),
        ]);
    }

    private function parseRss(string $xml, string $source): array
    {
        $jobs = [];
        preg_match_all('/<item>([\s\S]*?)<\/item>/i', $xml, $matches);
        foreach ($matches[1] as $item) {
            $title = $this->extractTag($item, 'title');
            $link = $this->extractTag($item, 'link');
            $pubDate = $this->extractTag($item, 'pubDate');
            $company = $this->extractTag($item, 'company') ?: $source . ' Posting';
            if ($title) {
                $jobs[] = [
                    'title' => html_entity_decode(strip_tags($title)),
                    'company' => html_entity_decode(strip_tags($company)),
                    'link' => trim($link),
                    'date' => $pubDate ? date('d M Y', strtotime($pubDate)) : '',
                    'source' => $source,
                    'location' => 'Remote',
                ];
            }
        }
        return $jobs;
    }

    private function parseKitaLulus(string $html): array
    {
        $jobs = [];
        preg_match_all('/<h3[^>]*>(.*?)<\/h3>/is', $html, $titles);
        preg_match_all('/href="(\/lowongan\/[^"]+)"/is', $html, $links);

        $uniqueLinks = array_values(array_unique(array_filter($links[1], function ($l) {
            return !str_contains($l, '?') && strlen(explode('/', $l)[2] ?? '') > 3;
        })));

        $titleref = $titles[1] ?? [];

        $limit = min(count($titleref), 10);
        for ($i = 0; $i < $limit; $i++) {
            $link = isset($uniqueLinks[$i]) ? "https://kerja.kitalulus.com" . $uniqueLinks[$i] : "https://kerja.kitalulus.com/id/lowongan";
            $cleanTitle = html_entity_decode(strip_tags($titleref[$i]));
            // KitaLulus doesn't easily expose company in regex, placeholder
            $jobs[] = [
                'title' => trim($cleanTitle),
                'company' => 'KitaLulus Employer',
                'link' => $link,
                'date' => date('d M Y'),
                'source' => 'KitaLulus',
                'location' => 'Indonesia',
            ];
        }
        return $jobs;
    }

    private function parseKalibrr(string $html): array
    {
        $jobs = [];
        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/is', $html, $next)) {
            $json = json_decode($next[1], true);
            $jobNodes = $json['props']['pageProps']['jobs'] ?? [];

            $limit = min(count($jobNodes), 15);
            for ($i = 0; $i < $limit; $i++) {
                $job = $jobNodes[$i];
                $company = $job['company']['name'] ?? 'Unknown Company';
                $code = $job['company']['code'] ?? 'co';
                $id = $job['id'] ?? '';
                $slug = $job['slug'] ?? 'job';

                if ($id) {
                    $jobs[] = [
                        'title' => trim($job['name']),
                        'company' => trim($company),
                        'link' => "https://www.kalibrr.com/c/{$code}/jobs/{$id}/{$slug}",
                        'date' => date('d M Y'),
                        'source' => 'Kalibrr',
                        'location' => 'Indonesia',
                    ];
                }
            }
        }
        return $jobs;
    }

    private function parseLinkedIn(string $html): array
    {
        $jobs = [];
        preg_match_all('/<div class="base-search-card__info"[\s\S]*?<h3 class="base-search-card__title">\s*(.*?)\s*<\/h3>[\s\S]*?<h4 class="base-search-card__subtitle">\s*<a[^>]*>\s*(.*?)\s*<\/a>[\s\S]*?<a class="base-card__full-link[^"]*" href="([^"?]+)/is', $html, $matches, PREG_SET_ORDER);

        $limit = min(count($matches), 10);
        for ($i = 0; $i < $limit; $i++) {
            $match = $matches[$i];
            $jobs[] = [
                'title' => trim(html_entity_decode(strip_tags($match[1]))),
                'company' => trim(html_entity_decode(strip_tags($match[2]))),
                'link' => trim($match[3]),
                'date' => date('d M Y'),
                'source' => 'LinkedIn',
                'location' => 'Indonesia/Remote',
            ];
        }
        return $jobs;
    }

    private function extractTag(string $xml, string $tag): ?string
    {
        if (preg_match("/<{$tag}><!\[CDATA\[(.*?)\]\]><\/{$tag}>/is", $xml, $m))
            return $m[1];
        if (preg_match("/<{$tag}>(.*?)<\/{$tag}>/is", $xml, $m))
            return $m[1];
        return null;
    }

    private function generateSearchLinks(string $query): array
    {
        $encoded = urlencode($query);
        return [
            ['name' => 'LinkedIn', 'url' => "https://www.linkedin.com/jobs/search/?keywords={$encoded}", 'icon' => '💼'],
            ['name' => 'KitaLulus', 'url' => "https://www.kitalulus.com/lowongan?search={$encoded}", 'icon' => '🇮🇩'],
            ['name' => 'Kalibrr', 'url' => "https://www.kalibrr.com/job-board/te/{$encoded}/co/Indonesia", 'icon' => '🔍'],
            ['name' => 'Glints', 'url' => "https://glints.com/id/opportunities/jobs/explore?keyword={$encoded}", 'icon' => '✨'],
            ['name' => 'Indeed', 'url' => "https://id.indeed.com/jobs?q={$encoded}", 'icon' => '📋'],
        ];
    }
}
