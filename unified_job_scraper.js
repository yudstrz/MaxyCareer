import fs from 'fs';

class UnifiedJobScraper {
    constructor() {
        this.headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        };
        this.jobs = [];
    }

    async _fetchUrl(url) {
        try {
            const response = await fetch(url, { headers: this.headers });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return await response.text();
        } catch (error) {
            console.error(`Error fetching ${url}:`, error);
            return null;
        }
    }

    async scrapeWeWorkRemotely() {
        console.log("Scraping We Work Remotely...");
        const url = "https://weworkremotely.com/remote-jobs.rss";
        const content = await this._fetchUrl(url);
        if (!content) return;

        try {
            const items = [...content.matchAll(/<item>([\s\S]*?)<\/item>/g)];
            for (const match of items) {
                const itemXml = match[1];
                const titleMatch = itemXml.match(/<title><!\[CDATA\[(.*?)\]\]><\/title>/) || itemXml.match(/<title>(.*?)<\/title>/);
                const linkMatch = itemXml.match(/<link>(.*?)<\/link>/);
                const pubDateMatch = itemXml.match(/<pubDate>(.*?)<\/pubDate>/);

                const title = titleMatch ? titleMatch[1] : "N/A";
                const link = linkMatch ? linkMatch[1] : "N/A";
                const pubDate = pubDateMatch ? pubDateMatch[1] : "N/A";

                let company = "N/A";
                let jobTitle = title;
                if (title.includes(":")) {
                    const parts = title.split(":");
                    company = parts[0].trim();
                    jobTitle = parts.slice(1).join(":").trim();
                }

                this.jobs.push({
                    source: 'We Work Remotely',
                    title: jobTitle.replace(/<[^>]+>/g, '').trim(),
                    company: company,
                    location: 'Remote',
                    link: link,
                    date: pubDate
                });
            }
        } catch (e) {
            console.error(`Error parsing We Work Remotely RSS: ${e}`);
        }
    }

    async scrapeRemoteOK() {
        console.log("Scraping RemoteOK...");
        const url = "https://remoteok.com/remote-jobs.rss";
        const content = await this._fetchUrl(url);
        if (!content) return;

        try {
            const items = [...content.matchAll(/<item>([\s\S]*?)<\/item>/g)];
            for (const match of items) {
                const itemXml = match[1];
                const titleMatch = itemXml.match(/<title>(.*?)<\/title>/);
                const linkMatch = itemXml.match(/<link>(.*?)<\/link>/);
                const pubDateMatch = itemXml.match(/<pubDate>(.*?)<\/pubDate>/);

                this.jobs.push({
                    source: 'RemoteOK',
                    title: titleMatch ? titleMatch[1].replace(/<[^>]+>/g, '').trim() : "N/A",
                    company: 'RemoteOK Posting',
                    location: 'Remote',
                    link: linkMatch ? linkMatch[1] : "N/A",
                    date: pubDateMatch ? pubDateMatch[1] : "N/A"
                });
            }
        } catch (e) {
            console.error(`Error parsing RemoteOK RSS: ${e}`);
        }
    }

    async scrapeKitaLulus(query = "Software Engineer") {
        console.log(`Scraping KitaLulus for ${query}...`);
        const url = `https://kerja.kitalulus.com/id/lowongan?q=${encodeURIComponent(query)}`;
        const content = await this._fetchUrl(url);
        if (!content) return;

        try {
            const titles = [...content.matchAll(/<h3[^>]*>(.*?)<\/h3>/g)];
            const links = [...content.matchAll(/href="(\/lowongan\/id\/[^"]+)"/g)];
            const uniqueLinks = [...new Set(links.map(l => l[1]))];

            for (let i = 0; i < Math.min(titles.length, 10); i++) {
                const cleanTitle = titles[i][1].replace(/<[^>]+>/g, '').trim();
                const link = i < uniqueLinks.length ? `https://kerja.kitalulus.com${uniqueLinks[i]}` : "https://kerja.kitalulus.com/id/lowongan";

                this.jobs.push({
                    source: 'KitaLulus',
                    title: cleanTitle,
                    company: 'KitaLulus Employer',
                    location: 'Indonesia',
                    link: link,
                    date: new Date().toUTCString()
                });
            }
        } catch (e) {
            console.error(`Error parsing KitaLulus: ${e}`);
        }
    }

    async scrapeKalibrr(query = "Software Engineer") {
        console.log(`Scraping Kalibrr for ${query}...`);
        const url = `https://www.kalibrr.id/job-board/te/${encodeURIComponent(query).toLowerCase()}/1`;
        const content = await this._fetchUrl(url);
        if (!content) return;

        try {
            const jobsMatch = [...content.matchAll(/<a[^>]*href="(\/c\/[^"]+\/jobs\/[^"]+)"[^>]*>[\s\S]*?<h2[^>]*>(.*?)<\/h2>/g)];

            for (let i = 0; i < Math.min(jobsMatch.length, 15); i++) {
                const linkPath = jobsMatch[i][1];
                const rawTitle = jobsMatch[i][2];
                const cleanTitle = rawTitle.replace(/<[^>]+>/g, '').trim();

                const companyMatch = linkPath.match(/\/c\/([^\/]+)\//);
                let company = "Unknown Company";
                if (companyMatch) {
                    company = companyMatch[1].replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                }

                this.jobs.push({
                    source: 'Kalibrr',
                    title: cleanTitle,
                    company: company,
                    location: 'Indonesia',
                    link: `https://www.kalibrr.com${linkPath}`,
                    date: new Date().toUTCString()
                });
            }
        } catch (e) {
            console.error(`Error parsing Kalibrr: ${e}`);
        }
    }

    async scrapeLinkedIn(query = "Software Engineer") {
        console.log(`Scraping LinkedIn for ${query}...`);
        const url = `https://www.linkedin.com/jobs/search?keywords=${encodeURIComponent(query)}&location=Indonesia`;
        const content = await this._fetchUrl(url);
        if (!content) return;

        try {
            let items = [...content.matchAll(/<a[^>]*class="base-card__full-link[^"]*"[^>]*href="([^"]+)"[^>]*>[\s\S]*?<span class="sr-only">\s*(.*?)\s*<\/span>/g)];
            if (items.length === 0) {
                items = [...content.matchAll(/<a[^>]*class="base-card__full-link[^"]*"[^>]*href="([^"]+)".*?<h3[^>]*class="base-search-card__title"[^>]*>\s*(.*?)\s*<\/h3>/g)];
            }

            for (let i = 0; i < Math.min(items.length, 15); i++) {
                const link = items[i][1];
                const rawTitle = items[i][2];
                const cleanTitle = rawTitle.replace(/<[^>]+>/g, '').trim();

                this.jobs.push({
                    source: 'LinkedIn',
                    title: cleanTitle,
                    company: 'LinkedIn Company',
                    location: 'Indonesia / Region',
                    link: link,
                    date: new Date().toUTCString()
                });
            }
        } catch (e) {
            console.error(`Error parsing LinkedIn: ${e}`);
        }
    }


    async runAll() {
        await this.scrapeWeWorkRemotely();
        await this.scrapeRemoteOK();
        await this.scrapeKitaLulus();
        await this.scrapeKalibrr();
        await this.scrapeLinkedIn();

        console.log(`\nSuccessfully scraped ${this.jobs.length} jobs.`);
        return this.jobs;
    }

    saveToJson(filename = "public/datasets/dtp_database.json") {
        fs.writeFileSync(filename, JSON.stringify(this.jobs, null, 4), 'utf-8');
        console.log(`Saved to ${filename}`);
    }
}

// Run the scraper
(async () => {
    const scraper = new UnifiedJobScraper();
    const jobs = await scraper.runAll();
    scraper.saveToJson();

    for (const job of jobs.slice(0, 5)) {
        console.log(`[${job.source}] ${job.title} @ ${job.company}`);
    }
})();
