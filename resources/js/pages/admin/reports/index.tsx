import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type BreadcrumbItem } from '@/types';
import { Wallet, TrendingUp, Building2, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useState, useEffect } from 'react';

// Definisi Interface Data
interface ReportData {
    id: number;
    name: string;
    total_transactions: number;
    gross_revenue: number;
    platform_fee: number;
    net_revenue: number;
}

interface PageProps {
    reports: ReportData[];
    summary: { gross: number; fee: number; net: number; };
    filters: { month: string; year: string; };
}

const currencyFormatter = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Laporan Keuangan', href: '/admin/reports' },
];

const MONTHS = [
    { value: '01', label: 'Januari' }, { value: '02', label: 'Februari' }, { value: '03', label: 'Maret' },
    { value: '04', label: 'April' }, { value: '05', label: 'Mei' }, { value: '06', label: 'Juni' },
    { value: '07', label: 'Juli' }, { value: '08', label: 'Agustus' }, { value: '09', label: 'September' },
    { value: '10', label: 'Oktober' }, { value: '11', label: 'November' }, { value: '12', label: 'Desember' },
];

// Array tahun dinamis
const currentYear = new Date().getFullYear();
const YEARS = Array.from({ length: 5 }, (_, i) => String(currentYear - 2 + i));

export default function AdminReportsIndex({ reports, summary, filters }: PageProps) {
    // State untuk menyimpan data kafe yang sedang ingin dicetak
    const [selectedReport, setSelectedReport] = useState<ReportData | null>(null);

    const handleFilterChange = (field: 'month' | 'year', value: string) => {
        router.get('/admin/reports', {
            ...filters,
            [field]: value
        }, { preserveState: true, preserveScroll: true });
    };

    // Fungsi untuk memicu dialog print per mitra
    const handlePrintCafe = (report: ReportData) => {
        setSelectedReport(report);
        // Beri jeda sedikit agar React selesai me-render layout cetaknya sebelum browser membuka dialog print
        setTimeout(() => {
            window.print();
        }, 150);
    };

    // Kembalikan state ke null setelah jendela print ditutup (baik disave maupun dicancel)
    useEffect(() => {
        const handleAfterPrint = () => setSelectedReport(null);
        window.addEventListener('afterprint', handleAfterPrint);
        return () => window.removeEventListener('afterprint', handleAfterPrint);
    }, []);

    const currentMonthLabel = MONTHS.find(m => m.value === filters.month)?.label || '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Keuangan Mitra" />

            {/* ===== TAMPILAN LAYAR UTAMA (Akan disembunyikan total saat sedang proses cetak) ===== */}
            <div className="flex flex-1 flex-col gap-6 rounded-xl p-6 print:hidden">
                
                {/* Header & Filter Area */}
                <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b pb-6 border-border/50">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">Laporan Pemasukan Mitra</h1>
                        <p className="text-muted-foreground mt-1">Rekapitulasi omset dan potongan komisi 10% platform.</p>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="flex flex-col gap-1">
                            <label className="text-xs text-muted-foreground font-medium uppercase tracking-wider">Bulan</label>
                            <select 
                                value={filters.month}
                                onChange={(e) => handleFilterChange('month', e.target.value)}
                                className="h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                            >
                                {MONTHS.map(m => (
                                    <option key={m.value} value={m.value}>{m.label}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className="text-xs text-muted-foreground font-medium uppercase tracking-wider">Tahun</label>
                            <select 
                                value={filters.year}
                                onChange={(e) => handleFilterChange('year', e.target.value)}
                                className="h-10 rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                            >
                                {YEARS.map(y => (
                                    <option key={y} value={y}>{y}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="bg-[#F9FFE8] border-[#BDEE63]/30">
                        <CardContent className="p-6">
                            <div className="flex items-center gap-4">
                                <div className="p-3 bg-[#BDEE63] rounded-xl">
                                    <Wallet className="h-6 w-6 text-black" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-black/60">Total Omset Kotor (Semua Kafe)</p>
                                    <p className="text-2xl font-bold text-black mt-1">{currencyFormatter.format(summary.gross)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-black border-zinc-800">
                        <CardContent className="p-6">
                            <div className="flex items-center gap-4">
                                <div className="p-3 bg-zinc-900 rounded-xl">
                                    <TrendingUp className="h-6 w-6 text-[#BDEE63]" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-zinc-400">Komisi CAFFESEEK (10%)</p>
                                    <p className="text-2xl font-bold text-white mt-1">{currencyFormatter.format(summary.fee)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center gap-4">
                                <div className="p-3 bg-muted rounded-xl">
                                    <Building2 className="h-6 w-6 text-muted-foreground" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Total Payout ke Mitra (90%)</p>
                                    <p className="text-2xl font-bold text-foreground mt-1">{currencyFormatter.format(summary.net)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Data Table */}
                <Card className="mt-4">
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Nama Kafe / Mitra</TableHead>
                                    <TableHead className="text-center">Total Reservasi</TableHead>
                                    <TableHead className="text-right">Omset Kotor</TableHead>
                                    <TableHead className="text-right">Potongan (10%)</TableHead>
                                    <TableHead className="text-right">Pencairan (Payout)</TableHead>
                                    <TableHead className="text-right pr-6">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {reports.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-12 text-muted-foreground">
                                            Tidak ada data transaksi pada periode ini.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    reports.map((report) => (
                                        <TableRow key={report.id}>
                                            <TableCell className="pl-6 font-semibold text-[#BDEE63]">{report.name}</TableCell>
                                            <TableCell className="text-center font-medium">
                                                {report.total_transactions} trx
                                            </TableCell>
                                            <TableCell className="text-right font-medium">
                                                {currencyFormatter.format(report.gross_revenue)}
                                            </TableCell>
                                            <TableCell className="text-right text-red-400 font-medium">
                                                - {currencyFormatter.format(report.platform_fee)}
                                            </TableCell>
                                            <TableCell className="text-right font-bold text-foreground">
                                                {currencyFormatter.format(report.net_revenue)}
                                            </TableCell>
                                            <TableCell className="text-right pr-6">
                                                {/* TOMBOL CETAK PER MITRA */}
                                                <Button 
                                                    size="sm"
                                                    onClick={() => handlePrintCafe(report)}
                                                    className="bg-white text-black hover:bg-gray-200 cursor-pointer"
                                                >
                                                    <Printer className="h-3 w-3 mr-1" /> Cetak
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>


            {/* ===== LAYOUT KHUSUS CETAK PDF PER MITRA ===== */}
            {/* Hanya akan dirender & terlihat di atas kertas/PDF ketika tombol "Cetak" diklik */}
            {selectedReport && (
                <div className="hidden print:block p-8 bg-white text-black min-h-screen w-full font-sans">
                    
                    {/* Kop Surat */}
                    <div className="text-center border-b-4 border-black pb-6 mb-8">
                        <h1 className="text-4xl font-extrabold uppercase tracking-widest mb-2">CAFFESEEK</h1>
                        <p className="text-xl font-bold">BUKTI PENCAIRAN DANA MITRA (PAYOUT)</p>
                        <p className="text-md mt-1 text-gray-600 font-medium">Periode Transaksi: {currentMonthLabel} {filters.year}</p>
                    </div>

                    {/* Informasi Penerima */}
                    <div className="mb-10">
                        <p className="text-sm font-bold text-gray-500 uppercase tracking-widest mb-1">Diberikan Kepada:</p>
                        <h2 className="text-2xl font-bold text-black">{selectedReport.name}</h2>
                    </div>

                    {/* Tabel Rincian */}
                    <table className="w-full text-left border-collapse mb-10">
                        <thead>
                            <tr className="border-b-2 border-black">
                                <th className="py-4 font-bold text-lg">Deskripsi Rincian</th>
                                <th className="py-4 text-right font-bold text-lg">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr className="border-b border-gray-300">
                                <td className="py-4 font-medium">Total Volume Reservasi Berhasil</td>
                                <td className="py-4 text-right font-medium">{selectedReport.total_transactions} Transaksi</td>
                            </tr>
                            <tr className="border-b border-gray-300 bg-gray-50">
                                <td className="py-4 font-medium pl-2">Pendapatan Kotor (Gross Revenue)</td>
                                <td className="py-4 text-right font-medium pr-2">{currencyFormatter.format(selectedReport.gross_revenue)}</td>
                            </tr>
                            <tr className="border-b border-gray-300">
                                <td className="py-4 font-medium text-gray-600 pl-2">Potongan Layanan Platform (10%)</td>
                                <td className="py-4 text-right font-medium text-red-600 pr-2">
                                    - {currencyFormatter.format(selectedReport.platform_fee)}
                                </td>
                            </tr>
                            <tr className="border-b-4 border-black bg-[#F9FFE8]">
                                <td className="py-5 font-bold text-xl pl-2">TOTAL PENCAIRAN BERSIH</td>
                                <td className="py-5 text-right font-extrabold text-2xl pr-2">
                                    {currencyFormatter.format(selectedReport.net_revenue)}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div className="mt-8 text-sm text-gray-500">
                        *Catatan: Dokumen ini diterbitkan secara otomatis oleh sistem CAFFESEEK dan sah digunakan sebagai laporan keuangan pembukuan mitra.
                    </div>

                    {/* Tanda Tangan */}
                    <div className="mt-20 flex justify-end">
                        <div className="text-center">
                            <p className="mb-2">Semarang, {new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                            <p className="font-bold text-gray-500">Platform CAFFESEEK</p>
                            <br /><br /><br /><br />
                            <p className="font-bold underline">Divisi Keuangan</p>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}