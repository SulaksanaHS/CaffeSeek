import { useState } from "react";
import { router, useForm } from "@inertiajs/react";
import { ArrowLeft, CheckCircle, UploadCloud, MessageCircle, Receipt, Copy } from "lucide-react";
import { toast, Toaster } from "sonner";

// Definisi Tipe Data
interface Reservation {
    id: number;
    cafe_id: number;
    customer_name: string;
    customer_whatsapp: string;
    reservation_date: string;
    status: string;
    total_price: string; 
    proof_of_payment_url: string | null;
    cafe: {
        id: number;
        name: string;
        whatsapp: string;
    };
}

const currencyFormatter = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
});

export default function Payment({ reservation }: { reservation: Reservation }) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const { data, setData, post, processing, errors } = useForm<{ proof_of_payment: File | null }>({
        proof_of_payment: null,
    });

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData("proof_of_payment", file);
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const submitPayment = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!data.proof_of_payment) {
            toast.error("Gagal", { description: "Pilih foto bukti transfer terlebih dahulu." });
            return;
        }

        post(`/reservations/${reservation.id}/upload-proof`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Berhasil!", { description: "Bukti transfer sedang diverifikasi admin." });
            },
            onError: () => {
                toast.error("Gagal Upload", { description: "Pastikan format gambar valid dan ukuran di bawah 5MB." });
            }
        });
    };

    const confirmToWhatsapp = () => {
        const message = `RESERVASI DIBAYAR - CAFFESEEK\n\nHalo, saya telah menyelesaikan pembayaran reservasi.\n\n*ID Invoice:* INV-${reservation.id}\n*Nama Pemesan:* ${reservation.customer_name}\n*Total Tagihan:* ${currencyFormatter.format(Number(reservation.total_price))}\n\nMohon cek dashboard admin CAFFESEEK untuk memverifikasi pesanan saya. Terima kasih!`;
        
        const adminWhatsapp = "6285741159939"; // Ganti dengan nomor WhatsApp admin kafe
        const url = `https://wa.me/${adminWhatsapp}?text=${encodeURIComponent(message)}`;
        window.open(url, "_blank");
    };

    const copyInvoice = () => {
        navigator.clipboard.writeText(`INV-${reservation.id}`);
        toast.success("Disalin!", { description: "Nomor Invoice berhasil disalin." });
    };

    return (
        <div className="min-h-screen bg-[#FDFDFD] text-[#1F1F1F] font-sans pb-12">
            <Toaster position="top-right" richColors />

            {/* Navbar Simple */}
            <nav className="w-full sticky top-0 z-50 bg-white shadow-sm border-b border-gray-100">
                <div className="max-w-3xl mx-auto flex items-center p-4">
                    <button
                        onClick={() => router.get(`/cafes/${reservation.cafe_id}`)}
                        className="p-2 hover:bg-gray-100 rounded-full transition cursor-pointer"
                    >
                        <ArrowLeft className="h-6 w-6" />
                    </button>
                    <h1 className="mx-auto font-bold text-lg tracking-wide">Penyelesaian Reservasi</h1>
                    <div className="w-10" /> {/* Spacer */}
                </div>
            </nav>

            <div className="max-w-xl mx-auto mt-8 px-4">
                
                {/* Status Badge */}
                {reservation.status === 'paid' && (
                    <div className="mb-6 flex flex-col items-center justify-center p-6 bg-[#F9FFE8] border border-[#BDEE63] rounded-3xl text-center">
                        <CheckCircle className="h-16 w-16 text-[#9AA05B] mb-3" />
                        <h2 className="text-2xl font-extrabold text-[#1F1F1F]">Pembayaran Diterima</h2>
                        <p className="text-gray-500 mt-2">Menunggu konfirmasi final dari pihak kafe.</p>
                    </div>
                )}

                <div className="bg-white border border-gray-200 shadow-xl shadow-gray-200/50 rounded-[32px] overflow-hidden">
                    
                    {/* Ringkasan Tagihan */}
                    <div className="p-6 md:p-8 border-b border-gray-100 bg-gray-50/50">
                        <div className="flex justify-between items-start mb-6">
                            <div>
                                <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Invoice</p>
                                <div className="flex items-center gap-2">
                                    <h3 className="text-lg font-mono font-bold">INV-{reservation.id}</h3>
                                    <button onClick={copyInvoice} className="text-gray-400 hover:text-black transition cursor-pointer">
                                        <Copy className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div className="text-right">
                                <p className="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Tanggal</p>
                                <h3 className="text-sm font-semibold">{reservation.reservation_date}</h3>
                            </div>
                        </div>

                        <div className="bg-white p-4 rounded-2xl border border-gray-100">
                            <p className="text-sm text-gray-500 mb-1">Total Tagihan ({reservation.cafe.name})</p>
                            <p className="text-3xl font-extrabold text-[#BDEE63] stroke-black text-stroke-1 text-shadow-sm">
                                {currencyFormatter.format(Number(reservation.total_price))}
                            </p>
                        </div>
                    </div>

                    {/* Area Pembayaran & Upload */}
                    <div className="p-6 md:p-8">
                        {reservation.status === 'pending' ? (
                            <>
                                <div className="flex flex-col items-center justify-center mb-8">
                                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-[#9AA05B] mb-4 text-center">
                                        Scan QRIS di bawah ini
                                    </p>
                                    
                                    {/* Memaksa QRIS berada di tengah */}
                                    <div className="flex items-center justify-center p-4 bg-white border-2 border-dashed border-gray-300 rounded-3xl shadow-sm w-fit mx-auto">
                                        <img 
                                            src="/images/qris.jpeg" 
                                            alt="QRIS CAFFESEEK" 
                                            className="w-64 h-64 object-contain rounded-xl"
                                        />
                                    </div>
                                    
                                    <p className="text-xs text-gray-400 mt-4 px-8 text-center">
                                        Transfer tepat sesuai nominal tagihan agar sistem dapat melakukan verifikasi.
                                    </p>
                                </div>

                                <form onSubmit={submitPayment} className="space-y-4" encType="multipart/form-data">
                                    <div className="relative border-2 border-dashed border-gray-300 rounded-3xl p-6 text-center hover:bg-gray-50 transition cursor-pointer">
                                        <input 
                                            type="file" 
                                            accept="image/*"
                                            onChange={handleFileChange}
                                            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                        />
                                        
                                        {previewUrl ? (
                                            <div className="flex flex-col items-center justify-center">
                                                <img src={previewUrl} alt="Preview Bukti" className="h-32 w-auto object-contain rounded-lg mb-3 shadow-md mx-auto" />
                                                <span className="text-sm text-[#9AA05B] font-semibold">Ganti Foto</span>
                                            </div>
                                        ) : (
                                            <div className="flex flex-col items-center justify-center text-gray-500">
                                                <UploadCloud className="h-10 w-10 mb-2 text-gray-400 mx-auto" />
                                                <span className="font-medium text-gray-900">Upload Bukti Transfer</span>
                                                <span className="text-xs mt-1">Format: JPG, PNG (Max 5MB)</span>
                                            </div>
                                        )}
                                    </div>
                                    {errors.proof_of_payment && <p className="text-red-500 text-xs text-center">{errors.proof_of_payment}</p>}

                                    {/* Perbaikan Tombol dengan Pseudo-class Tailwind (Pasti Berhasil) */}
                                    <button 
                                        type="submit" 
                                        disabled={processing || !data.proof_of_payment}
                                        className="w-full py-4 rounded-full font-bold transition flex items-center justify-center gap-2 cursor-pointer shadow-lg hover:shadow-xl bg-black text-white hover:bg-gray-800 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed disabled:shadow-none"
                                    >
                                        {processing ? "Mengunggah..." : "Kirim Bukti Pembayaran"}
                                    </button>
                                </form>
                            </>
                        ) : (
                            /* Tampilan Jika Sudah Upload (Status Paid/Completed) */
                            <div className="text-center space-y-6">
                                <div className="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <Receipt className="h-8 w-8 mx-auto text-gray-400 mb-2" />
                                    <p className="text-sm font-medium">Bukti transfer telah diserahkan.</p>
                                    <a href={reservation.proof_of_payment_url || "#"} target="_blank" className="text-xs text-blue-500 hover:underline">
                                        Lihat Foto Bukti
                                    </a>
                                </div>

                                <button 
                                    onClick={confirmToWhatsapp}
                                    className="w-full bg-[#BDEE63] hover:bg-[#a5db4b] text-[#1F1F1F] py-4 rounded-full font-bold transition flex items-center justify-center gap-2 cursor-pointer shadow-lg"
                                >
                                    <MessageCircle className="h-5 w-5" />
                                    Konfirmasi ke WhatsApp
                                </button>
                                
                                <button 
                                    onClick={() => router.get('/')}
                                    className="w-full py-4 text-gray-500 hover:text-black font-semibold text-sm underline transition cursor-pointer"
                                >
                                    Kembali ke Beranda
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}