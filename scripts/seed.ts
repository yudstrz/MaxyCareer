import { createClient } from '@libsql/client';
import dotenv from 'dotenv';
import crypto from 'crypto';

dotenv.config({ path: '.env.local' });
dotenv.config();

const db = createClient({
    url: process.env.TURSO_DATABASE_URL || 'file:local.db',
    authToken: process.env.TURSO_AUTH_TOKEN,
});

async function main() {
    console.log('Seeding database...');
    
    const leads = [
        {
            leadId: 'L001',
            namaPerusahaan: 'PT Teknologi Maju',
            kategoriBisnis: 'IT Services',
            sumberLead: 'Website',
            statusLead: 'New',
            nilaiPotensi: 50000000,
            probabilitas: 70,
            tanggalMasuk: new Date(new Date().setDate(new Date().getDate() - 5)).toISOString(),
            terakhirFollowUp: null,
            picSales: 'Budi',
            wilayah: 'Jakarta',
        },
        {
            leadId: 'L002',
            namaPerusahaan: 'CV Berkah Sentosa',
            kategoriBisnis: 'Retail',
            sumberLead: 'Referral',
            statusLead: 'In Progress',
            nilaiPotensi: 15000000,
            probabilitas: 40,
            tanggalMasuk: new Date(new Date().setDate(new Date().getDate() - 10)).toISOString(),
            terakhirFollowUp: new Date(new Date().setDate(new Date().getDate() - 1)).toISOString(),
            picSales: 'Andi',
            wilayah: 'Surabaya',
        },
        {
            leadId: 'L003',
            namaPerusahaan: 'Toko Makmur Jaya',
            kategoriBisnis: 'FMCG',
            sumberLead: 'Event',
            statusLead: 'Follow Up',
            nilaiPotensi: 120000000,
            probabilitas: 80,
            tanggalMasuk: new Date(new Date().setDate(new Date().getDate() - 14)).toISOString(),
            terakhirFollowUp: new Date(new Date().setDate(new Date().getDate() - 7)).toISOString(),
            picSales: 'Citra',
            wilayah: 'Bandung',
        },
        {
            leadId: 'L004',
            namaPerusahaan: 'PT Solusi Abadi',
            kategoriBisnis: 'Consulting',
            sumberLead: 'LinkedIn',
            statusLead: 'Lost',
            nilaiPotensi: 30000000,
            probabilitas: 0,
            tanggalMasuk: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString(),
            terakhirFollowUp: new Date(new Date().setDate(new Date().getDate() - 20)).toISOString(),
            picSales: 'Budi',
            wilayah: 'Jakarta',
            alasanLost: 'Budget tidak sesuai'
        }
    ];

    for (const lead of leads) {
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
                id, lead.leadId, lead.namaPerusahaan, lead.kategoriBisnis, lead.sumberLead, lead.statusLead,
                lead.nilaiPotensi, lead.probabilitas, lead.tanggalMasuk, lead.terakhirFollowUp, 
                lead.picSales, lead.wilayah, null, lead.alasanLost || null
            ]
        });
    }

    console.log('Seed completed successfully.');
}

main()
    .catch((e) => {
        console.error(e);
        process.exit(1);
    })
    .finally(() => {
        db.close();
    });
