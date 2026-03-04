import { pipeline } from '@xenova/transformers';
import fs from 'fs';

async function main() {
    console.log('Loading AI Model (all-MiniLM-L6-v2)...');
    const extractor = await pipeline('feature-extraction', 'Xenova/all-MiniLM-L6-v2', { quantized: true });

    // Load JSONL database
    const inputFile = 'public/datasets/DTP_Database.jsonl';
    const lines = fs.readFileSync(inputFile, 'utf-8').split('\n').filter(l => l.trim());
    const jobs = lines.map(l => JSON.parse(l));

    console.log(`Loaded ${jobs.length} jobs. Generating embeddings...`);

    const textsToEmbed = jobs.map(job => {
        const parts = [
            job.Okupasi || '',
            job.Area_Fungsi ? `in ${job.Area_Fungsi}` : '',
            job.Unit_Kompetensi || '',
            job.Kuk_Keywords || ''
        ].filter(p => p);
        return parts.join('. ');
    });

    // Generate embeddings one by one with progress
    const vectors = [];
    for (let i = 0; i < textsToEmbed.length; i++) {
        const output = await extractor(textsToEmbed[i], { pooling: 'mean', normalize: true });
        vectors.push(Array.from(output.data));
        if ((i + 1) % 20 === 0 || i === textsToEmbed.length - 1) {
            console.log(`Progress: ${i + 1}/${textsToEmbed.length}`);
        }
    }

    // Save to pon_index.json
    const outputFile = 'public/datasets/pon_index.json';
    fs.writeFileSync(outputFile, JSON.stringify(vectors));
    console.log(`Saved ${vectors.length} embeddings to ${outputFile}`);
}

main().catch(console.error);
