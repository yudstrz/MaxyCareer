import { NextResponse } from 'next/server';
import { db } from '@/lib/db';

export const dynamic = 'force-dynamic';

const TELEGRAM_BOT_TOKEN = process.env.TELEGRAM_BOT_TOKEN;

function formatCurrency(n: number): string {
    return '$' + n.toLocaleString('en-US');
}

function formatDate(d: Date | string | null): string {
    if (!d) return '-';
    const date = new Date(d);
    if (isNaN(date.getTime())) return '-';
    return date.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
}

function daysSince(d: Date | string | null): number {
    if (!d) return 999;
    const date = new Date(d);
    if (isNaN(date.getTime())) return 999;
    return Math.floor((Date.now() - date.getTime()) / 86400000);
}

export async function POST() {
    try {
        if (!TELEGRAM_BOT_TOKEN) {
            return NextResponse.json({ success: false, error: 'Telegram Bot Token not configured' }, { status: 500 });
        }

        let targetChatId = process.env.TELEGRAM_CHAT_ID;

        if (!targetChatId) {
            const updatesRes = await fetch(`https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/getUpdates`);
            const updates = await updatesRes.json();

            if (updates.ok && updates.result.length > 0) {
                const lastMsg = updates.result.reverse().find((u: any) => u.message?.chat?.id);
                if (lastMsg) targetChatId = lastMsg.message.chat.id.toString();
            }

            if (!targetChatId) {
                return NextResponse.json({
                    success: false,
                    error: 'Please send a message to your Telegram bot first so the Chat ID can be detected.'
                }, { status: 400 });
            }
        }

        // Fetch all leads
        const result = await db.execute('SELECT * FROM Lead ORDER BY createdAt DESC');
        const allLeads: any[] = result.rows as any[];

        // Stats
        const total = allLeads.length;
        const byStatus: Record<string, number> = {};
        allLeads.forEach(l => {
            const s = l.statusLead || 'Unknown';
            byStatus[s] = (byStatus[s] || 0) + 1;
        });

        const totalPotential = allLeads.reduce((s, l) => s + (l.nilaiPotensi || 0), 0);

        // Follow-up analysis
        const activeLeads = allLeads.filter(l => {
            const s = l.statusLead?.toLowerCase();
            return s !== 'lost' && s !== 'converted' && s !== 'won';
        });

        const neverFollowedUp: typeof allLeads = [];
        const overdueFollowUp: typeof allLeads = [];
        const dueSoonFollowUp: typeof allLeads = [];

        for (const lead of activeLeads) {
            if (!lead.terakhirFollowUp) {
                neverFollowedUp.push(lead);
                if (daysSince(lead.tanggalMasuk) > 3) overdueFollowUp.push(lead);
            } else {
                const d = daysSince(lead.terakhirFollowUp);
                if (d >= 7) overdueFollowUp.push(lead);
                else if (d >= 3) dueSoonFollowUp.push(lead);
            }
        }

        // Sort overdue by most urgent first
        overdueFollowUp.sort((a, b) => {
            const dA = a.terakhirFollowUp ? daysSince(a.terakhirFollowUp) : daysSince(a.tanggalMasuk);
            const dB = b.terakhirFollowUp ? daysSince(b.terakhirFollowUp) : daysSince(b.tanggalMasuk);
            return (dB || 0) - (dA || 0);
        });

        // Hot leads
        const hotLeads = allLeads.filter(l => l.statusLead?.toLowerCase() === 'hot' || l.statusLead?.toLowerCase() === 'warm');

        // Build date
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
        const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });

        // ── Build message ──
        let msg = '';

        // Header
        msg += `*LEAD SALES REPORT*\n`;
        msg += `━━━━━━━━━━━━━━━━━━━━\n`;
        msg += `${dateStr}\n`;
        msg += `${timeStr} WIB\n\n`;

        // Overview
        msg += `*SUMMARY*\n`;
        msg += `├ Total Leads: *${total}*\n`;
        msg += `├ Total Potential: *${formatCurrency(totalPotential)}*\n`;
        msg += `├ Active: *${activeLeads.length}*\n`;

        // Status breakdown
        const statusOrder = ['Hot', 'Warm', 'New', 'Cold', 'In Progress', 'Converted', 'Won', 'Lost'];

        msg += `└ Status:\n`;
        for (const status of statusOrder) {
            if (byStatus[status]) {
                msg += `    - ${status}: *${byStatus[status]}*\n`;
            }
        }
        // Include any unlisted statuses
        for (const [status, count] of Object.entries(byStatus)) {
            if (!statusOrder.includes(status)) {
                msg += `    - ${status}: *${count}*\n`;
            }
        }

        msg += `\n`;

        // Follow-up Alert
        if (overdueFollowUp.length > 0 || neverFollowedUp.length > 0) {
            msg += `*FOLLOW-UP ALERT*\n`;
            msg += `━━━━━━━━━━━━━━━━━━━━\n`;
            msg += `Overdue: *${overdueFollowUp.length}*\n`;
            msg += `Never contacted: *${neverFollowedUp.length}*\n`;
            msg += `Due soon: *${dueSoonFollowUp.length}*\n\n`;
        }

        // Top 5 overdue
        if (overdueFollowUp.length > 0) {
            msg += `*OVERDUE FOLLOW-UPS*\n`;
            msg += `━━━━━━━━━━━━━━━━━━━━\n`;
            const top5 = overdueFollowUp.slice(0, 5);
            for (let i = 0; i < top5.length; i++) {
                const lead = top5[i];
                const d = lead.terakhirFollowUp ? daysSince(lead.terakhirFollowUp) : daysSince(lead.tanggalMasuk);
                const fuDate = lead.terakhirFollowUp ? formatDate(lead.terakhirFollowUp) : 'Never';
                msg += `\n${i + 1}. *${lead.namaPerusahaan}*\n`;
                msg += `   ID: ${lead.leadId}\n`;
                msg += `   Status: ${lead.statusLead}\n`;
                msg += `   PIC: ${lead.picSales}\n`;
                msg += `   Last Follow-Up: ${fuDate} _(${d} days ago)_\n`;
                msg += `   Potential: ${formatCurrency(lead.nilaiPotensi)}\n`;
            }
            if (overdueFollowUp.length > 5) {
                msg += `\n_...and ${overdueFollowUp.length - 5} more leads_\n`;
            }
            msg += `\n`;
        }

        // Hot leads
        if (hotLeads.length > 0) {
            msg += `*HOT & WARM LEADS*\n`;
            msg += `━━━━━━━━━━━━━━━━━━━━\n`;
            const topHot = hotLeads.slice(0, 5);
            for (const lead of topHot) {
                msg += `- *${lead.namaPerusahaan}* — ${formatCurrency(lead.nilaiPotensi)} (${lead.probabilitas}%)\n`;
                msg += `  PIC: ${lead.picSales} | ${lead.wilayah}\n`;
            }
            msg += `\n`;
        }

        // Footer
        msg += `━━━━━━━━━━━━━━━━━━━━\n`;
        msg += `_Open the dashboard for full details._\n`;
        msg += `_Lead Sales Assistant_`;

        const response = await fetch(`https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                chat_id: targetChatId,
                text: msg,
                parse_mode: 'Markdown',
                disable_web_page_preview: true,
            }),
        });

        const tgResult = await response.json();

        if (tgResult.ok) {
            return NextResponse.json({ success: true, message: 'Report successfully sent to Telegram.' });
        } else {
            console.error('Telegram API error:', tgResult);
            return NextResponse.json({ success: false, error: tgResult.description }, { status: 500 });
        }

    } catch (error) {
        console.error('Failed to send Telegram report:', error);
        return NextResponse.json(
            { success: false, error: 'Internal Server Error' },
            { status: 500 }
        );
    }
}
