import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import { CheckCircle, ExternalLink, MessageCircle, XCircle } from 'lucide-react';
import { toast, Toaster } from 'sonner';

// Definisi Interface
interface ReservationItem {
    id: number;
    item_type: string;
    name: string;
    quantity: number;
}

interface Reservation {
    id: number;
    customer_name: string;
    customer_whatsapp: string;
    reservation_date: string;
    total_price: string;
    status: string;
    proof_of_payment_url: string | null;
    cafe: {
        name: string;
        whatsapp: string;
    };
    items: ReservationItem[];
}

const currencyFormatter = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Transaksi Reservasi', href: '/admin/reservations' },
];

export default function AdminReservationsIndex({ reservations }: { reservations: any }) {
    const data = reservations.data as Reservation[];

    // Fungsi Ajaib: Update Status + Buka WA ke Mitra
    const handleVerifyAndNotify = (reservation: Reservation) => {
        router.patch(`/admin/reservations/${reservation.id}/update-status`, { status: 'completed' }, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Reservasi Terverifikasi!', { description: 'Mengalihkan ke WhatsApp Mitra...' });
                
                // Membuat Draf Pesan Otomatis untuk Mitra (Kafe)
                let message = `*PESANAN LUNAS - CAFFESEEK*\n\n`;
                message += `Halo Tim *${reservation.cafe.name}*, ada reservasi baru yang sudah dibayar LUNAS melalui platform CAFFESEEK.\n\n`;
                message += `*Detail Pesanan:*\n`;
                message += `Invoice: INV-${reservation.id}\n`;
                message += `Atas Nama: ${reservation.customer_name}\n`;
                message += `Tanggal/Waktu: ${reservation.reservation_date}\n\n`;
                
                message += `*Daftar Booking:*\n`;
                reservation.items.forEach(item => {
                    message += `- ${item.name} (x${item.quantity})\n`;
                });

                message += `\n*Total Nominal:* ${currencyFormatter.format(Number(reservation.total_price))}\n`;
                message += `Dana pelanggan sudah kami amankan. Mohon segera dipersiapkan pesanannya ya. Terima kasih!`;

                // Membuka WA Web ke nomor Mitra Kafe
                const url = `https://wa.me/${reservation.cafe.whatsapp}?text=${encodeURIComponent(message)}`;
                window.open(url, '_blank');
            },
            onError: () => toast.error('Gagal memverifikasi pesanan.')
        });
    };

    const handleReject = (id: number) => {
        if(confirm("Yakin ingin menolak reservasi ini?")) {
            router.patch(`/admin/reservations/${id}/update-status`, { status: 'cancelled' });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaksi Reservasi" />
            <Toaster position="top-right" richColors />

            <div className="flex flex-1 flex-col gap-6 rounded-xl p-6">
                <div>
                    <h1 className="text-2xl font-semibold">Riwayat Transaksi & Reservasi</h1>
                    <p className="text-muted-foreground">Verifikasi bukti transfer dan hubungi mitra.</p>
                </div>

                <Card>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Invoice</TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>Caffe Mitra</TableHead>
                                    <TableHead>Total Tagihan</TableHead>
                                    <TableHead>Bukti</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right pr-6">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-12 text-gray-500">
                                            Belum ada transaksi reservasi.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    data.map((res) => (
                                        <TableRow key={res.id}>
                                            <TableCell className="pl-6 font-mono font-medium">INV-{res.id}</TableCell>
                                            <TableCell>
                                                <p className="font-semibold">{res.customer_name}</p>
                                                <p className="text-xs text-gray-500">{res.customer_whatsapp}</p>
                                            </TableCell>
                                            <TableCell className="font-medium text-[#9AA05B]">{res.cafe.name}</TableCell>
                                            <TableCell className="font-bold">
                                                {currencyFormatter.format(Number(res.total_price))}
                                            </TableCell>
                                            <TableCell>
                                                {res.proof_of_payment_url ? (
                                                    <a href={res.proof_of_payment_url} target="_blank" className="text-blue-500 flex items-center gap-1 hover:underline text-sm font-medium">
                                                        Cek Struk <ExternalLink className="h-3 w-3" />
                                                    </a>
                                                ) : (
                                                    <span className="text-xs text-red-400">Belum Upload</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {res.status === 'pending' && <Badge variant="outline" className="bg-yellow-50 text-yellow-600 border-yellow-200">Pending</Badge>}
                                                {res.status === 'paid' && <Badge variant="outline" className="bg-blue-50 text-blue-600 border-blue-200">Menunggu Verifikasi</Badge>}
                                                {res.status === 'completed' && <Badge variant="outline" className="bg-green-50 text-green-600 border-green-200">Selesai</Badge>}
                                                {res.status === 'cancelled' && <Badge variant="outline" className="bg-red-50 text-red-600 border-red-200">Dibatalkan</Badge>}
                                            </TableCell>
                                            <TableCell className="text-right pr-6">
                                                {/* Tombol Aksi hanya muncul jika user sudah upload bukti (status 'paid') */}
                                                {res.status === 'paid' && (
                                                    <div className="flex justify-end gap-2">
                                                        <Button 
                                                            size="sm" 
                                                            onClick={() => handleVerifyAndNotify(res)}
                                                            className="bg-black hover:bg-gray-800 text-[#BDEE63] cursor-pointer"
                                                        >
                                                            <CheckCircle className="h-4 w-4 mr-1" /> ACC & Kabari Mitra
                                                        </Button>
                                                        <Button 
                                                            variant="destructive" 
                                                            size="sm"
                                                            onClick={() => handleReject(res.id)}
                                                            className="cursor-pointer"
                                                        >
                                                            <XCircle className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                )}

                                                {res.status === 'completed' && (
                                                    <Button 
                                                        variant="outline" 
                                                        size="sm"
                                                        className="text-gray-400 cursor-not-allowed"
                                                    >
                                                        Telah Diverifikasi
                                                    </Button>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}