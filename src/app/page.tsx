'use client';

import { useState, useRef, useEffect, useMemo, useCallback, useTransition, memo } from 'react';
import Papa from 'papaparse';

/* ──────────────── TOAST SYSTEM ──────────────── */
type ToastType = 'success' | 'error' | 'info' | 'warning';
interface Toast { id: number; type: ToastType; message: string; }
let toastId = 0;

function useToast() {
  const [toasts, setToasts] = useState<Toast[]>([]);
  const show = useCallback((type: ToastType, message: string) => {
    const id = ++toastId;
    setToasts(prev => [...prev, { id, type, message }]);
    setTimeout(() => setToasts(prev => prev.filter(t => t.id !== id)), 4000);
  }, []);
  return { toasts, show };
}

function ToastContainer({ toasts }: { toasts: Toast[] }) {
  if (!toasts.length) return null;
  const icons: Record<ToastType, string> = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
  const colors: Record<ToastType, string> = {
    success: 'border-green-500/40 bg-green-500/10', error: 'border-red-500/40 bg-red-500/10',
    info: 'border-blue-500/40 bg-blue-500/10', warning: 'border-yellow-500/40 bg-yellow-500/10',
  };
  return (
    <div className="fixed top-6 right-6 z-50 flex flex-col gap-3 max-w-sm">
      {toasts.map(t => (
        <div key={t.id} className={`flex items-start gap-3 px-5 py-4 rounded-2xl border backdrop-blur-xl shadow-2xl animate-slide-in ${colors[t.type]}`}>
          <span className="text-lg mt-0.5">{icons[t.type]}</span>
          <p className="text-sm text-white/90 font-medium leading-relaxed">{t.message}</p>
        </div>
      ))}
    </div>
  );
}

/* ──────────────── HELPERS ──────────────── */
const HIDDEN_FIELDS = ['id', 'createdAt', 'updatedAt', 'statusFollowUp'];
const FIELD_LABELS: Record<string, string> = {
  leadId: 'Lead ID', namaPerusahaan: 'Company', kategoriBisnis: 'Category',
  sumberLead: 'Source', statusLead: 'Status', nilaiPotensi: 'Potential (USD)',
  probabilitas: 'Prob (%)', tanggalMasuk: 'Date Added', terakhirFollowUp: 'Last Follow-Up',
  picSales: 'PIC Sales', wilayah: 'Region', alasanLost: 'Lost Reason',
};

type FilterTab = 'all' | 'belum_fu' | 'terlambat' | 'perlu_fu' | 'hot';
const ROWS_PER_PAGE = 50;

function daysSince(dateStr: string | null): number | null {
  if (!dateStr) return null;
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return null;
  return Math.floor((Date.now() - d.getTime()) / 86400000);
}

const fuStatusColors: Record<string, { color: string; priority: number }> = {
  'Not Contacted': { color: 'orange', priority: 0 },
  'Ghosted': { color: 'red', priority: 1 },
  'Waiting Reply': { color: 'yellow', priority: 2 },
  'Active Chat': { color: 'green', priority: 3 },
  'Followed Up': { color: 'green', priority: 4 },
};

function getFollowUpStatus(lead: any): { label: string; color: string; priority: number } {
  // Use stored FU status if available
  if (lead.statusFollowUp) {
    const mapped = fuStatusColors[lead.statusFollowUp];
    if (mapped) return { label: lead.statusFollowUp, ...mapped };
    return { label: lead.statusFollowUp, color: 'gray', priority: 50 };
  }
  // Fallback: compute from dates
  const st = lead.statusLead?.toLowerCase();
  if (st === 'lost' || st === 'converted' || st === 'won')
    return { label: 'Closed', color: 'gray', priority: 99 };
  if (!lead.terakhirFollowUp) {
    const d = daysSince(lead.tanggalMasuk);
    return d !== null && d > 3
      ? { label: `No FU · ${d}d`, color: 'red', priority: 0 }
      : { label: 'Not Contacted', color: 'orange', priority: 1 };
  }
  const d = daysSince(lead.terakhirFollowUp);
  if (d === null) return { label: '-', color: 'gray', priority: 99 };
  if (d >= 7) return { label: 'Ghosted', color: 'red', priority: 0 };
  if (d >= 3) return { label: 'Waiting Reply', color: 'yellow', priority: 2 };
  if (d >= 1) return { label: 'Followed Up', color: 'green', priority: 3 };
  return { label: 'Active Chat', color: 'green', priority: 4 };
}

function formatCell(key: string, value: any): string {
  if (value === null || value === undefined || value === '') return '-';
  const k = key.toLowerCase();
  if (k.includes('tanggal') || k.includes('follow') || k.includes('date')) {
    const d = new Date(value);
    if (!isNaN(d.getTime())) return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
  }
  if (k.includes('potensi') || k.includes('nilai') || k.includes('value') || k.includes('amount') || k.includes('potential')) {
    const n = parseFloat(value);
    if (!isNaN(n)) return '$' + n.toLocaleString('en-US');
  }
  if (k.includes('probabilitas') || k.includes('prob')) return `${value}%`;
  return String(value);
}

/* ──────────────── MEMOIZED TABLE ROW ──────────────── */
const statusColors: Record<string, string> = {
  Won: 'bg-green-500/15 text-green-400 border-green-500/30',
  Converted: 'bg-green-500/15 text-green-400 border-green-500/30',
  Lost: 'bg-red-500/15 text-red-400 border-red-500/30',
  New: 'bg-blue-500/15 text-blue-400 border-blue-500/30',
  Hot: 'bg-orange-500/15 text-orange-400 border-orange-500/30',
  Warm: 'bg-yellow-500/15 text-yellow-400 border-yellow-500/30',
  Cold: 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30',
  'In Progress': 'bg-yellow-500/15 text-yellow-400 border-yellow-500/30',
};

const fuBadge: Record<string, string> = {
  red: 'bg-red-500/15 text-red-400 border-red-500/30',
  orange: 'bg-orange-500/15 text-orange-400 border-orange-500/30',
  yellow: 'bg-yellow-500/15 text-yellow-400 border-yellow-500/30',
  green: 'bg-green-500/15 text-green-400 border-green-500/30',
  gray: 'bg-gray-500/10 text-gray-500 border-gray-500/20',
};

interface TableRowProps {
  lead: any;
  index: number;
  columns: string[];
  isSelected: boolean;
  onToggle: (id: string) => void;
}

const TableRow = memo(function TableRow({ lead, index, columns, isSelected, onToggle }: TableRowProps) {
  const fus = getFollowUpStatus(lead);
  const rowBg = isSelected ? 'bg-blue-500/8' : fus.color === 'red' ? 'bg-red-500/[0.04]' : fus.color === 'orange' ? 'bg-orange-500/[0.03]' : '';

  return (
    <tr
      className={`group hover:bg-white/[0.04] transition-colors duration-100 cursor-pointer ${rowBg}`}
      onClick={() => onToggle(lead.id)}
    >
      <td className="px-4 py-3" onClick={e => e.stopPropagation()}>
        <input type="checkbox" checked={isSelected} onChange={() => onToggle(lead.id)} className="w-3.5 h-3.5 rounded accent-blue-500 cursor-pointer" />
      </td>
      <td className="px-3 py-3">
        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide border whitespace-nowrap ${fuBadge[fus.color] || fuBadge.gray}`}>
          {fus.color === 'red' && (
            <span className="relative flex h-1.5 w-1.5">
              <span className="animate-ping absolute h-full w-full rounded-full bg-red-400 opacity-75"></span>
              <span className="relative rounded-full h-1.5 w-1.5 bg-red-500"></span>
            </span>
          )}
          {fus.label}
        </span>
      </td>
      {columns.map(col => (
        <td key={col} className="px-4 py-3 whitespace-nowrap text-gray-300 text-[13px]">
          {col === 'statusLead' ? (
            <span className={`inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border ${statusColors[lead[col]] || 'bg-gray-500/15 text-gray-400 border-gray-500/30'}`}>
              {lead[col]}
            </span>
          ) : col === 'namaPerusahaan' ? (
            <span className="font-semibold text-white group-hover:text-blue-400 transition-colors">{lead[col]}</span>
          ) : (
            formatCell(col, lead[col])
          )}
        </td>
      ))}
    </tr>
  );
});

/* ──────────────── MAIN DASHBOARD ──────────────── */
export default function Dashboard() {
  const [leads, setLeads] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [deleting, setDeleting] = useState(false);
  const [activeFilter, setActiveFilter] = useState<FilterTab>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [visibleCount, setVisibleCount] = useState(ROWS_PER_PAGE);
  const [isPending, startTransition] = useTransition();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const { toasts, show: toast } = useToast();

  // ── Fetch + Auto-refresh ──
  const fetchLeads = useCallback(async (silent = false) => {
    try {
      const res = await fetch('/api/leads');
      const data = await res.json();
      if (data.success) setLeads(data.leads);
    } catch {
      if (!silent) toast('error', 'Failed to load leads data.');
    } finally {
      setLoading(false);
    }
  }, [toast]);

  useEffect(() => {
    fetchLeads();
    const interval = setInterval(() => fetchLeads(true), 30000);
    return () => clearInterval(interval);
  }, [fetchLeads]);

  // ── CSV Upload ──
  const handleFileUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;
    setUploading(true);
    Papa.parse(file, {
      header: true, skipEmptyLines: true,
      complete: async (results) => {
        try {
          const res = await fetch('/api/leads/import', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ data: results.data }) });
          const data = await res.json();
          if (data.success) { toast('success', data.message); fetchLeads(); }
          else toast('error', `Import failed: ${data.error}`);
        } catch { toast('error', 'An error occurred during import.'); }
        finally { setUploading(false); if (fileInputRef.current) fileInputRef.current.value = ''; }
      },
      error: () => { toast('error', 'Failed to read CSV file.'); setUploading(false); }
    });
  };

  // ── Telegram (non-blocking) ──
  const handleSendTelegram = async () => {
    if (!confirm('Send lead summary report to Telegram?')) return;
    toast('info', 'Sending report...');
    try {
      const res = await fetch('/api/notify/telegram', { method: 'POST' });
      const data = await res.json();
      if (data.success) toast('success', 'Report sent to Telegram successfully.');
      else toast('error', `Failed: ${data.error}`);
    } catch { toast('error', 'Failed to send report.'); }
  };

  // ── Selection ──
  const toggleSelect = useCallback((id: string) => {
    setSelectedIds(prev => { const n = new Set(prev); n.has(id) ? n.delete(id) : n.add(id); return n; });
  }, []);

  // ── Delete ──
  const deleteLeads = async (payload: any) => {
    setDeleting(true);
    try {
      const res = await fetch('/api/leads', { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
      const data = await res.json();
      if (data.success) { toast('success', data.message); setSelectedIds(new Set()); fetchLeads(); }
      else toast('error', data.error);
    } catch { toast('error', 'Failed to delete data.'); }
    finally { setDeleting(false); }
  };

  // ── Follow-up analysis (memoized) ──
  const fu = useMemo(() => {
    const active = leads.filter(l => !['lost', 'converted', 'won'].includes(l.statusLead?.toLowerCase()));
    return {
      belumFU: active.filter(l => !l.terakhirFollowUp),
      terlambat: active.filter(l => getFollowUpStatus(l).color === 'red'),
      perluFU: active.filter(l => getFollowUpStatus(l).color === 'yellow'),
      hot: leads.filter(l => ['hot', 'warm'].includes(l.statusLead?.toLowerCase())),
    };
  }, [leads]);

  // ── Filter + Sort + Search (memoized) ──
  const filteredLeads = useMemo(() => {
    const map: Record<FilterTab, any[]> = {
      all: leads, belum_fu: fu.belumFU, terlambat: fu.terlambat, perlu_fu: fu.perluFU, hot: fu.hot,
    };
    let result = [...(map[activeFilter] || leads)];

    if (searchQuery.trim()) {
      const q = searchQuery.toLowerCase().trim();
      result = result.filter(l =>
        l.namaPerusahaan?.toLowerCase().includes(q) ||
        l.leadId?.toLowerCase().includes(q) ||
        l.picSales?.toLowerCase().includes(q) ||
        l.wilayah?.toLowerCase().includes(q) ||
        l.statusLead?.toLowerCase().includes(q)
      );
    }

    return result.sort((a, b) => getFollowUpStatus(a).priority - getFollowUpStatus(b).priority);
  }, [leads, activeFilter, fu, searchQuery]);

  // ── Paginated visible leads ──
  const visibleLeads = useMemo(() => filteredLeads.slice(0, visibleCount), [filteredLeads, visibleCount]);
  const hasMore = visibleCount < filteredLeads.length;

  // ── Filter switch (non-blocking with useTransition) ──
  const switchFilter = useCallback((tab: FilterTab) => {
    startTransition(() => {
      setActiveFilter(tab);
      setSelectedIds(new Set());
      setVisibleCount(ROWS_PER_PAGE);
    });
  }, []);

  const toggleSelectAll = useCallback(() => {
    setSelectedIds(prev => prev.size === visibleLeads.length ? new Set() : new Set(visibleLeads.map(l => l.id)));
  }, [visibleLeads]);

  const dynamicColumns = useMemo(() =>
    leads.length > 0 ? Object.keys(leads[0]).filter(k => !HIDDEN_FIELDS.includes(k)) : []
    , [leads]);

  const totalPotential = useMemo(() => leads.reduce((s, l) => s + (parseFloat(l.nilaiPotensi) || 0), 0), [leads]);
  const allSelected = visibleLeads.length > 0 && selectedIds.size === visibleLeads.length;

  const tabs: { key: FilterTab; label: string; count: number; activeColor: string }[] = [
    { key: 'all', label: 'All', count: leads.length, activeColor: 'border-white/30 bg-white/10 text-white' },
    { key: 'terlambat', label: 'Overdue', count: fu.terlambat.length, activeColor: 'border-red-500/40 bg-red-500/10 text-red-400' },
    { key: 'belum_fu', label: 'No Follow-Up', count: fu.belumFU.length, activeColor: 'border-orange-500/40 bg-orange-500/10 text-orange-400' },
    { key: 'perlu_fu', label: 'Due Soon', count: fu.perluFU.length, activeColor: 'border-yellow-500/40 bg-yellow-500/10 text-yellow-400' },
    { key: 'hot', label: 'Hot / Warm', count: fu.hot.length, activeColor: 'border-orange-500/40 bg-orange-500/10 text-orange-400' },
  ];

  const filterTitle: Record<FilterTab, string> = {
    all: 'All Leads', terlambat: 'Overdue Follow-Ups', belum_fu: 'Never Followed Up',
    perlu_fu: 'Follow-Up Due Soon', hot: 'Hot & Warm Leads',
  };

  return (
    <div className="min-h-screen p-4 md:p-8 max-w-[1440px] mx-auto">
      <ToastContainer toasts={toasts} />

      {/* ── Header ── */}
      <header className="mb-8 p-6 glass-panel rounded-3xl border border-white/10">
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
          <div>
            <h1 className="text-4xl font-bold text-white mb-1 tracking-tight">Lead Sales Assistant</h1>
            <p className="text-gray-400 text-sm">CSV Import · Follow-Up Tracking · Telegram Reports</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <input type="file" accept=".csv" ref={fileInputRef} onChange={handleFileUpload} className="hidden" />
            <button onClick={handleSendTelegram} className="px-5 py-2.5 text-sm font-medium rounded-xl bg-white/5 border border-white/10 text-gray-300 hover:bg-white/10 hover:text-white transition-all">Send Report</button>
            <button onClick={() => fileInputRef.current?.click()} disabled={uploading}
              className="px-5 py-2.5 text-sm font-medium rounded-xl bg-blue-500 text-white hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/20 disabled:opacity-50">
              {uploading ? 'Importing...' : 'Import CSV'}
            </button>
          </div>
        </div>
      </header>

      {/* ── Alert Banner ── */}
      {fu.terlambat.length > 0 && (
        <div className="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 flex flex-col sm:flex-row items-start sm:items-center gap-4 animate-fade-in">
          <div className="flex items-center gap-3 flex-1">
            <div>
              <p className="text-red-400 font-semibold">{fu.terlambat.length} leads are overdue for follow-up</p>
              <p className="text-red-400/60 text-xs mt-0.5">{fu.belumFU.length} never contacted · {fu.perluFU.length} due soon</p>
            </div>
          </div>
          <button onClick={() => switchFilter('terlambat')}
            className="px-4 py-2 text-xs font-semibold rounded-xl bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-all whitespace-nowrap">
            View Now →
          </button>
        </div>
      )}

      {/* ── Stats Cards ── */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        {[
          { label: 'Total Leads', value: leads.length, color: 'text-white border-white/10', tab: 'all' as FilterTab },
          { label: 'Overdue', value: fu.terlambat.length, color: 'text-red-400 border-red-500/20', tab: 'terlambat' as FilterTab },
          { label: 'No Follow-Up', value: fu.belumFU.length, color: 'text-orange-400 border-orange-500/20', tab: 'belum_fu' as FilterTab },
          { label: 'Due Soon', value: fu.perluFU.length, color: 'text-yellow-400 border-yellow-500/20', tab: 'perlu_fu' as FilterTab },
        ].map((card, i) => (
          <div key={i} onClick={() => switchFilter(card.tab)}
            className={`glass-panel rounded-2xl p-5 border cursor-pointer hover:scale-[1.02] active:scale-[0.98] transition-all duration-150 ${card.color}`}>
            <p className="text-[10px] uppercase tracking-widest mb-1.5 opacity-80">{card.label}</p>
            <p className="text-2xl font-bold">{card.value}</p>
          </div>
        ))}
        <div className="glass-panel rounded-2xl p-5 border border-purple-500/20 col-span-2 md:col-span-1">
          <p className="text-[10px] text-purple-400 uppercase tracking-widest mb-1.5 opacity-80">Total Potential</p>
          <p className="text-xl font-bold text-purple-400">${totalPotential.toLocaleString('en-US')}</p>
        </div>
      </div>

      {/* ── Filter Tabs ── */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div className="flex flex-wrap gap-2">
          {tabs.map(t => (
            <button key={t.key} onClick={() => switchFilter(t.key)}
              className={`px-4 py-2 text-xs font-semibold rounded-xl border transition-all duration-150 ${activeFilter === t.key ? t.activeColor : 'border-white/5 text-gray-500 hover:border-white/15 hover:text-gray-300'
                }`}>
              {t.label} <span className="ml-1 opacity-60">({t.count})</span>
            </button>
          ))}
          {isPending && <span className="text-xs text-gray-500 self-center ml-2 animate-pulse">Filtering...</span>}
        </div>

        {/* Search Input */}
        <div className="relative w-full md:w-80">
          <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500">🔍</span>
          <input
            type="text"
            placeholder="Search company, ID, PIC..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-2.5 bg-white/5 border border-white/10 rounded-xl text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500/50 focus:bg-white/10 transition-all"
          />
          {searchQuery && (
            <button
              onClick={() => setSearchQuery('')}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-white transition-colors"
            >
              ✕
            </button>
          )}
        </div>
      </div>

      {/* ── Table ── */}
      <div className="glass-panel rounded-3xl overflow-hidden border border-white/10">
        <div className="p-5 border-b border-white/5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white/[0.03]">
          <div>
            <h2 className="text-lg font-bold text-white">{filterTitle[activeFilter]}</h2>
            <p className="text-xs text-gray-500 mt-0.5">
              {filteredLeads.length} leads{selectedIds.size > 0 && <span className="text-blue-400"> · {selectedIds.size} selected</span>}
              <span className="text-gray-600 ml-2">· auto-refresh 30d</span>
            </p>
          </div>
          {leads.length > 0 && (
            <div className="flex flex-wrap items-center gap-2">
              {selectedIds.size > 0 && (
                <button onClick={() => { if (confirm(`Delete ${selectedIds.size} leads?`)) deleteLeads({ ids: Array.from(selectedIds) }); }}
                  disabled={deleting}
                  className="px-3.5 py-1.5 text-xs font-medium rounded-lg bg-red-500/15 text-red-400 border border-red-500/30 hover:bg-red-500/25 transition-all disabled:opacity-50">
                  Delete {selectedIds.size}
                </button>
              )}
              <button onClick={() => { if (confirm(`DELETE ALL ${leads.length} leads?`) && confirm('Are you sure?')) deleteLeads({ deleteAll: true }); }}
                disabled={deleting}
                className="px-3.5 py-1.5 text-xs font-medium rounded-lg bg-white/5 text-gray-500 border border-white/10 hover:bg-red-500/10 hover:text-red-400 hover:border-red-500/20 transition-all disabled:opacity-50">
                Delete All
              </button>
            </div>
          )}
        </div>

        {loading ? (
          <div className="flex flex-col justify-center items-center h-60 gap-4">
            <div className="relative">
              <div className="h-12 w-12 rounded-full border-4 border-blue-500/20"></div>
              <div className="absolute top-0 h-12 w-12 rounded-full border-4 border-blue-500 border-t-transparent animate-spin"></div>
            </div>
            <p className="text-gray-500 text-sm animate-pulse">Loading leads data...</p>
          </div>
        ) : filteredLeads.length === 0 ? (
          <div className="flex flex-col items-center py-16 text-gray-500">
            <p className="text-base font-medium">
              {activeFilter === 'all' ? 'No leads data yet.' :
                activeFilter === 'terlambat' ? 'Great! No overdue follow-ups.' :
                  activeFilter === 'belum_fu' ? 'All leads have been followed up.' :
                    activeFilter === 'perlu_fu' ? 'No follow-ups due at this time.' :
                      'No Hot/Warm leads.'}
            </p>
            {activeFilter !== 'all' && (
              <button onClick={() => switchFilter('all')} className="text-xs text-blue-400 hover:underline mt-3">← Back to All</button>
            )}
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-[10px] text-gray-400 uppercase tracking-[0.15em] bg-black/30 sticky top-0 z-10">
                  <tr>
                    <th className="px-4 py-3.5 w-10">
                      <input type="checkbox" checked={allSelected} onChange={toggleSelectAll} className="w-3.5 h-3.5 rounded accent-blue-500 cursor-pointer" />
                    </th>
                    <th className="px-3 py-3.5 font-semibold">FU Status</th>
                    {dynamicColumns.map(col => (
                      <th key={col} className="px-4 py-3.5 font-semibold whitespace-nowrap">{FIELD_LABELS[col] || col}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-white/[0.04]">
                  {visibleLeads.map((lead, i) => (
                    <TableRow
                      key={lead.id}
                      lead={lead}
                      index={i}
                      columns={dynamicColumns}
                      isSelected={selectedIds.has(lead.id)}
                      onToggle={toggleSelect}
                    />
                  ))}
                </tbody>
              </table>
            </div>

            {/* Load more */}
            {hasMore && (
              <div className="p-4 flex justify-center border-t border-white/5">
                <button onClick={() => setVisibleCount(prev => prev + ROWS_PER_PAGE)}
                  className="px-6 py-2.5 text-xs font-semibold rounded-xl bg-white/5 border border-white/10 text-gray-400 hover:bg-white/10 hover:text-white transition-all">
                  Show {Math.min(ROWS_PER_PAGE, filteredLeads.length - visibleCount)} more ({filteredLeads.length - visibleCount} remaining)
                </button>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
