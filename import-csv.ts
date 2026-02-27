import fs from 'fs';
import path from 'path';
import Papa from 'papaparse';
import { createClient } from '@libsql/client';
import dotenv from 'dotenv';
import crypto from 'crypto';

dotenv.config({ path: '.env.local' });
dotenv.config();

const db = createClient({
    url: process.env.TURSO_DATABASE_URL || '',
    authToken: process.env.TURSO_AUTH_TOKEN,
});
async function main() {
    const csvFilePath = path.join(process.cwd(), 'Sales B2B - Sales B2B.csv');

    if (!fs.existsSync(csvFilePath)) {
        console.error(`CSV file not found at ${csvFilePath}`);
        process.exit(1);
    }

    const fileContent = fs.readFileSync(csvFilePath, 'utf8');

    console.log('Parsing CSV...');
    const parsed = Papa.parse(fileContent, {
        header: true,
        skipEmptyLines: true,
    });

    if (parsed.errors.length > 0) {
        console.error('Errors parsing CSV:', parsed.errors);
        process.exit(1);
    }

    const leads = parsed.data as any[];
    console.log(`Found ${leads.length} leads. Importing to database...`);

    let importedCount = 0;
    let skippedCount = 0;

    for (const row of leads) {
        try {
            // Internal getVal helper for robust mapping
            const getVal = (possibleKeys: string[]) => {
                const normalizedKeys = possibleKeys
                    .map(pk => pk.toLowerCase().replace(/[^a-z0-9]/g, ''))
                    .filter(Boolean);

                let key = Object.keys(row).find(k => {
                    const normalized = k.toLowerCase().replace(/[^a-z0-9]/g, '');
                    return normalizedKeys.some(npk => normalized === npk);
                });
                if (key) return row[key];

                key = Object.keys(row).find(k => {
                    const normalized = k.toLowerCase().replace(/[^a-z0-9]/g, '');
                    return normalizedKeys.some(npk => normalized.includes(npk) || npk.includes(normalized));
                });
                return key ? row[key] : undefined;
            };

            // Utility to sanitize phone numbers
            const sanitizePhone = (val: string | undefined) => val ? val.replace(/[^0-9+]/g, '') : undefined;

            // Utility to parse numbers
            const sanitizeNumber = (val: any): number => {
                if (!val) return 0;
                let str = String(val).trim();
                str = str.replace(/[Rr][Pp]\.?\s*/g, '').replace(/\$\s*/g, '').replace(/USD\s*/gi, '').replace(/\s/g, '').replace(/%/g, '');

                const lastCommaIter = str.lastIndexOf(',');
                const lastDotIter = str.lastIndexOf('.');
                if (lastCommaIter > lastDotIter && lastDotIter !== -1) {
                    str = str.replace(/\./g, '').replace(',', '.');
                } else if (lastDotIter > lastCommaIter && lastCommaIter !== -1) {
                    str = str.replace(/,/g, '');
                } else if (lastCommaIter !== -1 && lastDotIter === -1) {
                    if (str.length - lastCommaIter - 1 === 3) str = str.replace(/,/g, '');
                    else str = str.replace(',', '.');
                } else if (lastDotIter !== -1 && lastCommaIter === -1) {
                    if (str.split('.').length > 2 || str.length - lastDotIter - 1 === 3) str = str.replace(/\./g, '');
                }
                const num = parseFloat(str);
                return isNaN(num) ? 0 : num;
            };

            // Utility to safely parse dates
            const sanitizeDate = (val: any) => {
                if (!val) return null;
                let str = String(val).trim();
                if (!str || str === '-' || str === 'N/A' || str === 'n/a') return null;
                const ddmmyyyy = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
                if (ddmmyyyy) {
                    const [, day, month, year] = ddmmyyyy;
                    const d = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
                    if (!isNaN(d.getTime())) return d;
                }
                const yyyymmdd = str.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
                if (yyyymmdd) {
                    const [, year, month, day] = yyyymmdd;
                    const d = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
                    if (!isNaN(d.getTime())) return d;
                }
                const parsed = new Date(str);
                return isNaN(parsed.getTime()) ? null : parsed;
            };

            const leadId = String(getVal(['leadId', 'id', 'lead']) || '').trim();
            if (!leadId) {
                skippedCount++;
                continue;
            }

            const namaPerusahaan = String(getVal(['namaPerusahaan', 'perusahaan', 'company', 'name']) || 'Unknown');
            const phoneField = sanitizePhone(getVal(['telepon', 'phone', 'whatsapp', 'wa']));
            const tanggalMasuk = sanitizeDate(getVal(['tanggalMasuk', 'tanggal', 'date', 'added'])) || new Date();
            const terakhirFollowUp = sanitizeDate(getVal(['terakhirFollowUp', 'lastFU', 'lastFollowUp', 'followup']));
            const statusLead = String(getVal(['statusLead', 'status']) || 'New');
            const nilaiPotensi = sanitizeNumber(getVal(['nilaiPotensi', 'potensi', 'value', 'potential']));
            const probabilitas = Math.round(sanitizeNumber(getVal(['probabilitas', 'prob', 'probability'])));
            const statusFollowUp = getVal(['statusFollowUp', 'fuStatus', 'followUpStatus']) || null;
            const alasanLost = getVal(['alasanLost', 'alasan', 'reason', 'lostReason']) || null;

            const id = crypto.randomUUID();
            await db.execute({
                sql: `
                    INSERT INTO Lead (
                        id, leadId, namaPerusahaan, kategoriBisnis, sumberLead, statusLead, 
                        nilaiPotensi, probabilitas, tanggalMasuk, terakhirFollowUp, picSales, 
                        wilayah, statusFollowUp, alasanLost, createdAt, updatedAt
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                    ON CONFLICT(leadId) DO UPDATE SET 
                        namaPerusahaan=EXCLUDED.namaPerusahaan,
                        kategoriBisnis=EXCLUDED.kategoriBisnis,
                        sumberLead=EXCLUDED.sumberLead,
                        statusLead=EXCLUDED.statusLead,
                        nilaiPotensi=EXCLUDED.nilaiPotensi,
                        probabilitas=EXCLUDED.probabilitas,
                        tanggalMasuk=EXCLUDED.tanggalMasuk,
                        terakhirFollowUp=EXCLUDED.terakhirFollowUp,
                        picSales=EXCLUDED.picSales,
                        wilayah=EXCLUDED.wilayah,
                        statusFollowUp=EXCLUDED.statusFollowUp,
                        alasanLost=EXCLUDED.alasanLost,
                        updatedAt=CURRENT_TIMESTAMP
                `,
                args: [
                    id, leadId, namaPerusahaan,
                    getVal(['kategoriBisnis', 'kategori', 'category']) || 'Umum',
                    getVal(['sumberLead', 'sumber', 'source']) || (phoneField ? `Phone: ${phoneField}` : 'Import CSV'),
                    statusLead, nilaiPotensi, probabilitas,
                    tanggalMasuk instanceof Date ? tanggalMasuk.toISOString() : null,
                    terakhirFollowUp instanceof Date ? terakhirFollowUp.toISOString() : null,
                    getVal(['picSales', 'pic', 'sales']) || 'Unassigned',
                    getVal(['wilayah', 'region', 'area']) || 'Unknown',
                    statusFollowUp === '-' ? null : statusFollowUp,
                    alasanLost === '-' ? null : alasanLost
                ]
            });
            importedCount++;
        } catch (error) {
            console.error(`Error importing row with Lead ID: ${row['Lead ID']}`, error);
        }
    }

    console.log(`Import completed! Successfully imported ${importedCount} new leads. Skipped ${skippedCount} existing leads.`);
}

main()
    .catch((e) => {
        console.error(e);
        process.exit(1);
    })
    .finally(() => {
        db.close();
    });
