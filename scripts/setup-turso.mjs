// Script to create the Lead table on Turso
// Run with: node scripts/setup-turso.mjs

import { createClient } from '@libsql/client';

const client = createClient({
    url: process.env.TURSO_DATABASE_URL || 'libsql://lead-assistant-yudstrz.aws-us-east-2.turso.io',
    authToken: process.env.TURSO_AUTH_TOKEN || 'eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCJ9.eyJhIjoicnciLCJpYXQiOjE3NzIwODQ1NDksImlkIjoiMDE5Yzk4NzgtMjkwMS03NDViLWFiMDgtN2FmNTFmNWVlNjQwIiwicmlkIjoiYjU3MGZhYmMtMzJlYy00NTkwLTg0ZmYtZWJmNzgzNTFmMGE0In0.RXS7l0v4Nm0PAMLd07zf9qiEkSrC0HxIBnnJ-RWlnGiGEhGwrUO_LELGAD4WQAdszkW_0cSrKASUdqW3aWfJAQ',
});

async function setupDatabase() {
    console.log('Creating Lead table on Turso...');

    await client.execute(`
    CREATE TABLE IF NOT EXISTS Lead (
      id TEXT PRIMARY KEY NOT NULL,
      leadId TEXT NOT NULL UNIQUE,
      namaPerusahaan TEXT NOT NULL,
      kategoriBisnis TEXT NOT NULL,
      sumberLead TEXT NOT NULL,
      statusLead TEXT NOT NULL,
      nilaiPotensi REAL NOT NULL,
      probabilitas INTEGER NOT NULL,
      tanggalMasuk DATETIME NOT NULL,
      terakhirFollowUp DATETIME,
      picSales TEXT NOT NULL,
      wilayah TEXT NOT NULL,
      alasanLost TEXT,
      createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
  `);

    console.log('✅ Lead table created successfully!');

    // Verify
    const result = await client.execute('SELECT name FROM sqlite_master WHERE type="table"');
    console.log('Tables:', result.rows.map(r => r.name));
}

setupDatabase().catch(console.error);
