import { NextResponse } from 'next/server';
import { db } from '@/lib/db';

export const dynamic = 'force-dynamic';

export async function GET() {
    try {
        const result = await db.execute('SELECT * FROM Lead ORDER BY createdAt DESC');
        return NextResponse.json({ success: true, leads: result.rows });
    } catch (error) {
        console.error('Failed to fetch leads:', error);
        return NextResponse.json(
            { success: false, error: 'Failed to fetch leads' },
            { status: 500 }
        );
    }
}

export async function DELETE(request: Request) {
    try {
        const body = await request.json();
        const { ids, deleteAll } = body;

        if (deleteAll) {
            const result = await db.execute('DELETE FROM Lead');
            return NextResponse.json({ success: true, message: `All ${result.rowsAffected} leads deleted successfully.` });
        }

        if (!ids || !Array.isArray(ids) || ids.length === 0) {
            return NextResponse.json({ success: false, error: 'No data selected.' }, { status: 400 });
        }

        const placeholders = ids.map(() => '?').join(',');
        const result = await db.execute({
            sql: `DELETE FROM Lead WHERE id IN (${placeholders})`,
            args: ids
        });

        return NextResponse.json({ success: true, message: `${result.rowsAffected} leads deleted successfully.` });

    } catch (error) {
        console.error('Failed to delete leads:', error);
        return NextResponse.json(
            { success: false, error: 'Failed to delete data.' },
            { status: 500 }
        );
    }
}
