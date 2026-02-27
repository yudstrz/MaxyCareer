export const formatRupiah = (value: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(value);
};

export const formatDate = (date: Date | string | null): string => {
    if (!date) return '-';
    try {
        const d = new Date(date);
        if (isNaN(d.getTime())) return '-';
        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(d);
    } catch (e) {
        return '-';
    }
};

export const normalizeDate = (date: Date | string | null): Date | null => {
    if (!date) return null;
    const d = new Date(date);
    if (isNaN(d.getTime())) return null;
    const normalized = new Date(d.getTime());
    normalized.setHours(0, 0, 0, 0);
    return normalized;
};

export const isLateFollowUp = (terakhirFollowUp: Date | null, tanggalMasuk: Date) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const normalizedEntry = normalizeDate(tanggalMasuk);
    if (!normalizedEntry) return false;

    if (!terakhirFollowUp) {
        // If never followed up, check if more than 3 days since received
        const diffTime = Math.abs(today.getTime() - normalizedEntry.getTime());
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays > 3;
    }

    const normalizedLastFU = normalizeDate(terakhirFollowUp);
    if (!normalizedLastFU) return false;

    const diffTime = Math.abs(today.getTime() - normalizedLastFU.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 7;
};
