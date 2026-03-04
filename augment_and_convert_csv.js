import fs from 'fs';
import path from 'path';

function augmentData(rows, targetRows = 200) {
    if (rows.length >= targetRows) return rows.slice(0, targetRows);

    const additionalNeeded = targetRows - rows.length;
    const newRows = [];

    for (let i = 0; i < additionalNeeded; i++) {
        const sourceRow = rows[Math.floor(Math.random() * rows.length)];
        const prefix = i % 2 === 0 ? 'Senior' : 'Junior';

        newRows.push({
            OkupasiID: `PON-AUG-${String(i + 1).padStart(3, '0')}`,
            Area_Fungsi: sourceRow.Area_Fungsi,
            Okupasi: `${prefix} ${sourceRow.Okupasi}`,
            Unit_Kompetensi: sourceRow.Unit_Kompetensi,
            Kuk_Keywords: sourceRow.Kuk_Keywords
        });
    }

    return [...rows, ...newRows];
}

function parseCSV(content, separator = ';') {
    const lines = content.trim().split('\n');
    const headers = lines[0].split(separator).map(h => h.trim().replace(/"/g, ''));
    const rows = [];

    for (let i = 1; i < lines.length; i++) {
        const values = lines[i].split(separator).map(v => v.trim().replace(/"/g, ''));
        if (values.length === headers.length) {
            const row = {};
            headers.forEach((h, idx) => { row[h] = values[idx]; });
            rows.push(row);
        }
    }
    return rows;
}

function main() {
    const csvFile = 'DTP.csv';
    const outputFile = path.join('public', 'datasets', 'DTP_Database.jsonl');

    console.log(`Reading ${csvFile}...`);
    const content = fs.readFileSync(csvFile, 'utf-8');
    const rows = parseCSV(content, ';');

    console.log(`Original rows: ${rows.length}`);
    const augmented = augmentData(rows, 200);
    console.log(`Augmented rows: ${augmented.length}`);

    // Ensure output directory exists
    fs.mkdirSync(path.dirname(outputFile), { recursive: true });

    // Write JSONL
    const jsonlContent = augmented.map(r => JSON.stringify(r)).join('\n') + '\n';
    fs.writeFileSync(outputFile, jsonlContent, 'utf-8');

    console.log(`Saved to ${outputFile}`);
    console.log('Done!');
}

main();
