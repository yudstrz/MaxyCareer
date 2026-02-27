import { NextResponse } from 'next/server';
import { db } from '@/lib/db';
import crypto from 'crypto';

export const dynamic = 'force-dynamic';

export async function POST(request: Request) {
    try {
        const body = await request.json();
        const { data } = body;

        if (!data || !Array.isArray(data)) {
            console.error('Import Error: Invalid data format. Data is not an array.');
            return NextResponse.json({ success: false, error: 'Invalid data format' }, { status: 400 });
        }

        console.log(`Starting import of ${data.length} rows.`);

        // Format and validate data for Prisma
        const formattedLeads = data
            .map((row: any, index: number) => {
                // Try to find matching keys (exact normalized match first, then substring fallback)
                const getVal = (possibleKeys: string[]) => {
                    const normalizedKeys = possibleKeys
                        .map(pk => pk.toLowerCase().replace(/[^a-z0-9]/g, ''))
                        .filter(Boolean);

                    // Phase 1: Exact normalized match (highest priority)
                    let key = Object.keys(row).find(k => {
                        const normalized = k.toLowerCase().replace(/[^a-z0-9]/g, '');
                        if (!normalized) return false;
                        return normalizedKeys.some(npk => normalized === npk);
                    });
                    if (key) return row[key];

                    // Phase 2: Substring match (fallback)
                    key = Object.keys(row).find(k => {
                        const normalized = k.toLowerCase().replace(/[^a-z0-9]/g, '');
                        if (!normalized) return false;
                        return normalizedKeys.some(npk =>
                            normalized.includes(npk) || npk.includes(normalized)
                        );
                    });
                    return key ? row[key] : undefined;
                };

                // Utility to sanitize phone numbers (remove spaces, dashes, etc)
                const sanitizePhone = (val: string | undefined) => val ? val.replace(/[^0-9+]/g, '') : undefined;

                // Utility to parse numbers: robust for US and Indonesian formats
                const sanitizeNumber = (val: any): number => {
                    if (!val) return 0;
                    let str = String(val).trim();
                    str = str.replace(/[Rr][Pp]\.?\s*/g, '').replace(/\$\s*/g, '').replace(/USD\s*/gi, '').replace(/\s/g, '').replace(/%/g, '');

                    // Determine if the string uses Indonesian formatting (dots for thousands, comma for decimals)
                    // or US formatting (commas for thousands, dot for decimals).

                    const lastCommaIter = str.lastIndexOf(',');
                    const lastDotIter = str.lastIndexOf('.');

                    if (lastCommaIter > lastDotIter && lastDotIter !== -1) {
                        // Example: 15.000,50 -> Indonesian
                        str = str.replace(/\./g, '').replace(',', '.');
                    } else if (lastDotIter > lastCommaIter && lastCommaIter !== -1) {
                        // Example: 15,000.50 -> US
                        str = str.replace(/,/g, '');
                    } else if (lastCommaIter !== -1 && lastDotIter === -1) {
                        // Only commas. If exactly 3 digits after the last comma, it's likely a US thousands separator (e.g., 15,000)
                        // If 1 or 2 digits, it's likely an Indonesian decimal (e.g., 15000,50)
                        if (str.length - lastCommaIter - 1 === 3) {
                            str = str.replace(/,/g, '');
                        } else {
                            str = str.replace(',', '.');
                        }
                    } else if (lastDotIter !== -1 && lastCommaIter === -1) {
                        // Only dots. If multiple dots, or single dot with exactly 3 digits after it, likely Indonesian thousands.
                        // Otherwise, US decimal.
                        if (str.split('.').length > 2 || str.length - lastDotIter - 1 === 3) {
                            str = str.replace(/\./g, '');
                        }
                        // If single dot and not exactly 3 digits after, leave it as is (US decimal)
                    }

                    const num = parseFloat(str);
                    return isNaN(num) ? 0 : num;
                };

                // Utility to safely parse dates (handles DD/MM/YYYY, DD-Mon-YYYY, YYYY-MM-DD, etc.)
                const sanitizeDate = (val: any) => {
                    if (!val) return null;
                    let str = String(val).trim();
                    // Treat "-", "N/A", "" as null
                    if (!str || str === '-' || str === 'N/A' || str === 'n/a') return null;

                    // Try DD/MM/YYYY or DD-MM-YYYY
                    const ddmmyyyy = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
                    if (ddmmyyyy) {
                        const [, day, month, year] = ddmmyyyy;
                        const d = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
                        if (!isNaN(d.getTime())) return d;
                    }

                    // Try YYYY-MM-DD or YYYY/MM/DD
                    const yyyymmdd = str.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/);
                    if (yyyymmdd) {
                        const [, year, month, day] = yyyymmdd;
                        const d = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
                        if (!isNaN(d.getTime())) return d;
                    }

                    // Fallback: try native Date parsing (handles ISO strings, "Feb 10, 2026", etc.)
                    const parsed = new Date(str);
                    return isNaN(parsed.getTime()) ? null : parsed;
                };

                const rawId = getVal(['leadId', 'id', 'lead']);
                const leadId = rawId ? String(rawId).trim() : `L-${Date.now()}-${index}`;

                const rawName = getVal(['namaPerusahaan', 'perusahaan', 'company', 'companyName', 'nama', 'name']);
                const namaPerusahaan = rawName ? String(rawName).trim() : 'Unknown Company';

                const phoneField = sanitizePhone(getVal(['telepon', 'phone', 'whatsapp', 'wa', 'no', 'nohp']));

                const tanggalMasuk = sanitizeDate(getVal(['tanggalMasuk', 'tanggal', 'date', 'entryDate'])) || new Date();
                const terakhirFollowUp = sanitizeDate(getVal(['terakhirFollowUp', 'lastFU', 'lastFollowUp', 'followup', 'lastfollow']));

                const rawFUStatus = getVal(['statusFollowUp', 'fuStatus', 'followUpStatus', 'fu status', 'fustatus']);

                return {
                    leadId,
                    namaPerusahaan,
                    kategoriBisnis: getVal(['kategoriBisnis', 'kategori', 'category']) || 'Umum',
                    sumberLead: getVal(['sumberLead', 'sumber', 'source']) || (phoneField ? `Phone: ${phoneField}` : 'Import CSV'),
                    statusLead: getVal(['statusLead', 'status']) || 'New',
                    nilaiPotensi: sanitizeNumber(getVal(['nilaiPotensi', 'potensi', 'value', 'amount', 'nilai', 'potential'])),
                    probabilitas: Math.round(sanitizeNumber(getVal(['probabilitas', 'prob', 'probability']))),
                    tanggalMasuk,
                    terakhirFollowUp,
                    picSales: getVal(['picSales', 'pic', 'sales']) || 'Unassigned',
                    wilayah: getVal(['wilayah', 'region', 'area']) || 'Unknown',
                    statusFollowUp: (() => {
                        if (rawFUStatus && rawFUStatus !== '-' && rawFUStatus !== 'N/A') return String(rawFUStatus).trim();
                        return null;
                    })(),
                    alasanLost: (() => {
                        const val = getVal(['alasanLost', 'alasan', 'reason', 'lostReason']);
                        if (!val || val === '-' || val === 'N/A') return null;
                        return String(val).trim();
                    })(),
                };
            })
            .filter((lead: any) => lead.leadId !== '' && lead.namaPerusahaan !== '');

        console.log(`Formatted ${formattedLeads.length} leads successfully.`);

        if (formattedLeads.length === 0) {
            console.warn('Import Warning: No valid leads found after formatting.');
            return NextResponse.json({
                success: false,
                error: 'CSV file is empty or could not be read.'
            }, { status: 400 });
        }

        let importedCount = 0;
        for (const lead of formattedLeads) {
            try {
                const id = crypto.randomUUID();
                const sql = `
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
                `;
                const args = [
                    id, lead.leadId, lead.namaPerusahaan, lead.kategoriBisnis, lead.sumberLead, lead.statusLead,
                    lead.nilaiPotensi, lead.probabilitas,
                    lead.tanggalMasuk instanceof Date ? lead.tanggalMasuk.toISOString() : null,
                    lead.terakhirFollowUp instanceof Date ? lead.terakhirFollowUp.toISOString() : null,
                    lead.picSales, lead.wilayah, lead.statusFollowUp, lead.alasanLost
                ];
                await db.execute({ sql, args });
                importedCount++;
            } catch (e: any) {
                console.error(`Failed to import lead ${lead.leadId}:`, e.message);
            }
        }

        console.log(`Successfully imported ${importedCount} leads.`);
        return NextResponse.json({ success: true, message: `Successfully imported ${importedCount} leads.` });

    } catch (error: any) {
        console.error('Failed to import leads:', error.message);
        return NextResponse.json(
            { success: false, error: 'Failed to process CSV import' },
            { status: 500 }
        );
    }
}
