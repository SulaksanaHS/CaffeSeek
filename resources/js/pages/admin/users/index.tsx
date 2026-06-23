import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus, SearchIcon, Store } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast, Toaster } from 'sonner';

interface Cafe {
    id: string;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    owner_cafe: string | null;
    created_at: string;
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cafes: Cafe[];
    filters: {
        search: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Management User', href: '/admin/users' },
];

export default function UsersIndex({ users, cafes, filters }: Props) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isAssignCafeModalOpen, setIsAssignCafeModalOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState<User | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<User | null>(null);
    const [assignTarget, setAssignTarget] = useState<User | null>(null);
    const [cafeComboOpen, setCafeComboOpen] = useState(false);
    const [selectedCafeId, setSelectedCafeId] = useState<string>('');

    const [formData, setFormData] = useState({
        name: '',
        email: '',
        password: '',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isInitialRender, setIsInitialRender] = useState(true);

    useEffect(() => {
        if (isInitialRender) {
            setIsInitialRender(false);
            return;
        }
        const debounce = setTimeout(() => fetchUsers(1), 400);
        return () => clearTimeout(debounce);
    }, [searchTerm]);

    const fetchUsers = (page: number) => {
        router.get('/admin/users', { page, search: searchTerm || undefined }, { replace: true, preserveScroll: true });
    };

    const resetForm = () => {
        setFormData({ name: '', email: '', password: '' });
        setErrors({});
    };

    const openCreateModal = () => {
        resetForm();
        setIsCreateModalOpen(true);
    };

    const openEditModal = (user: User) => {
        setSelectedUser(user);
        setFormData({ name: user.name, email: user.email, password: '' });
        setErrors({});
        setIsEditModalOpen(true);
    };

    const openAssignCafeModal = (user: User) => {
        setAssignTarget(user);
        setSelectedCafeId(user.owner_cafe ?? '');
        setIsAssignCafeModalOpen(true);
    };

    const handleSubmit = () => {
        setIsSubmitting(true);
        setErrors({});

        if (isEditModalOpen && selectedUser) {
            router.put(`/admin/users/${selectedUser.id}`, formData, {
                preserveScroll: true,
                onSuccess: () => {
                    setIsEditModalOpen(false);
                    resetForm();
                    toast.success('Berhasil!', { description: 'User berhasil diperbarui.' });
                },
                onError: (formErrors) => setErrors(formErrors as Record<string, string>),
                onFinish: () => setIsSubmitting(false),
            });
            return;
        }

        router.post('/admin/users', formData, {
            preserveScroll: true,
            onSuccess: () => {
                setIsCreateModalOpen(false);
                resetForm();
                toast.success('Berhasil!', { description: 'User berhasil ditambahkan.' });
            },
            onError: (formErrors) => setErrors(formErrors as Record<string, string>),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const handleAssignCafe = () => {
        if (!assignTarget) return;
        setIsSubmitting(true);
        router.patch(`/admin/users/${assignTarget.id}/assign-cafe`, { owner_cafe: selectedCafeId || null }, {
            preserveScroll: true,
            onSuccess: () => {
                setIsAssignCafeModalOpen(false);
                setAssignTarget(null);
                toast.success('Berhasil!', { description: 'Cafe berhasil ditetapkan ke user.' });
            },
            onError: () => toast.error('Gagal!', { description: 'Terjadi kesalahan saat menetapkan cafe.' }),
            onFinish: () => setIsSubmitting(false),
        });
    };

    const confirmDelete = (user: User) => {
        setDeleteTarget(user);
        setIsDeleteModalOpen(true);
    };

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(`/admin/users/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsDeleteModalOpen(false);
                setDeleteTarget(null);
                toast.success('Berhasil!', { description: 'User berhasil dihapus.' });
            },
        });
    };

    const selectedCafeName = cafes.find((c) => c.id === selectedCafeId)?.name;
    const assignTargetCafeName = cafes.find((c) => c.id === assignTarget?.owner_cafe)?.name;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Management User" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4 lg:p-6 lg:pb-12 xl:gap-6 xl:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Management User</h1>
                        <p className="text-sm text-muted-foreground">Kelola pengguna yang dapat mengakses sistem.</p>
                    </div>
                    <Button onClick={openCreateModal} className="cursor-pointer">
                        <Plus className="mr-2 h-4 w-4" />
                        Tambah User
                    </Button>
                </div>

                <Card>
                    <CardHeader className="p-4 sm:p-6 pb-0 sm:pb-0">
                        <div className="relative w-full sm:w-80">
                            <SearchIcon className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                type="search"
                                placeholder="Cari user berdasarkan nama atau email..."
                                className="pl-8"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="p-4 sm:p-6 pt-4 sm:pt-6">
                        <div className="rounded-md border overflow-x-auto">
                            <Table className="min-w-[700px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Caffe & Resto</TableHead>
                                        <TableHead className="text-end">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.data.length > 0 ? (
                                        users.data.map((user) => {
                                            const cafeName = cafes.find((c) => c.id === user.owner_cafe)?.name;
                                            return (
                                                <TableRow key={user.id}>
                                                    <TableCell className="font-medium">{user.name}</TableCell>
                                                    <TableCell>{user.email}</TableCell>
                                                    <TableCell>
                                                        {cafeName ? (
                                                            <Badge variant="outline" className="gap-1">
                                                                <Store className="h-3 w-3" />
                                                                {cafeName}
                                                            </Badge>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground italic">Belum ditetapkan</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-end">
                                                        <div className="flex justify-end gap-2">
                                                            <Button variant="outline" size="sm" onClick={() => openAssignCafeModal(user)} className="cursor-pointer gap-1">
                                                                <Store className="h-3 w-3" />
                                                                Assign Cafe
                                                            </Button>
                                                            <Button variant="outline" size="sm" onClick={() => openEditModal(user)} className="cursor-pointer">
                                                                Edit
                                                            </Button>
                                                            <Button variant="destructive" size="sm" onClick={() => confirmDelete(user)} className="cursor-pointer">
                                                                Delete
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={4} className="h-24 text-center">
                                                Tidak ada data user.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                        {users.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Showing {(users.current_page - 1) * users.per_page + 1}–{Math.min(users.current_page * users.per_page, users.total)} of {users.total} results
                                </p>
                                <div className="flex gap-2">
                                    <Button variant="outline" size="sm" disabled={users.current_page === 1} onClick={() => fetchUsers(users.current_page - 1)}>
                                        Previous
                                    </Button>
                                    <Button variant="outline" size="sm" disabled={users.current_page === users.last_page} onClick={() => fetchUsers(users.current_page + 1)}>
                                        Next
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* ── Create Modal ─────────────────────────────────── */}
            <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah User Baru</DialogTitle>
                        <DialogDescription>Tambahkan user dengan role mitra.</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Lengkap</Label>
                            <Input id="name" value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} />
                            {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} />
                            {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <Input id="password" type="password" value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })} />
                            {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsCreateModalOpen(false)}>Batal</Button>
                        <Button onClick={handleSubmit} disabled={isSubmitting}>{isSubmitting ? 'Menyimpan...' : 'Simpan'}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Edit Modal ───────────────────────────────────── */}
            <Dialog open={isEditModalOpen} onOpenChange={setIsEditModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit User</DialogTitle>
                        <DialogDescription>Ubah data user. Kosongkan password jika tidak ingin mengubahnya.</DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">Nama Lengkap</Label>
                            <Input id="edit-name" value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} />
                            {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-email">Email</Label>
                            <Input id="edit-email" type="email" value={formData.email} onChange={(e) => setFormData({ ...formData, email: e.target.value })} />
                            {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-password">Password (Optional)</Label>
                            <Input id="edit-password" type="password" value={formData.password} onChange={(e) => setFormData({ ...formData, password: e.target.value })} placeholder="Biarkan kosong jika tidak diubah" />
                            {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsEditModalOpen(false)}>Batal</Button>
                        <Button onClick={handleSubmit} disabled={isSubmitting}>{isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Assign Cafe Modal ────────────────────────────── */}
            <Dialog open={isAssignCafeModalOpen} onOpenChange={setIsAssignCafeModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Store className="h-5 w-5" />
                            Penetapan Cafe & Resto
                        </DialogTitle>
                        <DialogDescription>
                            Tetapkan hak akses Cafe & Resto untuk <strong>{assignTarget?.name}</strong>.
                            {assignTargetCafeName && (
                                <span className="block mt-1 text-xs">
                                    Saat ini mengelola: <Badge variant="secondary" className="ml-1">{assignTargetCafeName}</Badge>
                                </span>
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-4 space-y-4">
                        <div className="space-y-2">
                            <Label>Pilih Cafe & Resto</Label>
                            <Popover open={cafeComboOpen} onOpenChange={setCafeComboOpen} modal={true}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={cafeComboOpen}
                                        className="w-full justify-between font-normal"
                                    >
                                        {selectedCafeId
                                            ? (selectedCafeName ?? 'Cafe tidak ditemukan')
                                            : 'Pilih cafe...'}
                                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-full p-0" align="start">
                                    <Command>
                                        <CommandInput placeholder="Cari cafe..." />
                                        <CommandList>
                                            <CommandEmpty>Cafe tidak ditemukan.</CommandEmpty>
                                            <CommandGroup>
                                                <CommandItem
                                                    value="__none__"
                                                    onSelect={() => {
                                                        setSelectedCafeId('');
                                                        setCafeComboOpen(false);
                                                    }}
                                                >
                                                    <Check className={`mr-2 h-4 w-4 ${!selectedCafeId ? 'opacity-100' : 'opacity-0'}`} />
                                                    <span className="italic text-muted-foreground">— Tidak ada (cabut akses)</span>
                                                </CommandItem>
                                                {cafes.map((cafe) => (
                                                    <CommandItem
                                                        key={cafe.id}
                                                        value={cafe.name}
                                                        onSelect={() => {
                                                            setSelectedCafeId(cafe.id);
                                                            setCafeComboOpen(false);
                                                        }}
                                                    >
                                                        <Check className={`mr-2 h-4 w-4 ${selectedCafeId === cafe.id ? 'opacity-100' : 'opacity-0'}`} />
                                                        <Store className="mr-2 h-4 w-4 text-muted-foreground" />
                                                        {cafe.name}
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                            <p className="text-xs text-muted-foreground">
                                User dengan role <strong>mitra</strong> hanya dapat mengakses data cafe yang ditetapkan.
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsAssignCafeModalOpen(false)}>Batal</Button>
                        <Button onClick={handleAssignCafe} disabled={isSubmitting} className="gap-2">
                            <Store className="h-4 w-4" />
                            {isSubmitting ? 'Menyimpan...' : 'Simpan Penetapan'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Delete Confirmation ──────────────────────────── */}
            <Dialog open={isDeleteModalOpen} onOpenChange={setIsDeleteModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Konfirmasi Hapus</DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus user <strong>{deleteTarget?.name}</strong>? Data ini akan dipindahkan ke tempat sampah (soft delete).
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsDeleteModalOpen(false)}>Batal</Button>
                        <Button variant="destructive" onClick={handleDelete}>Ya, Hapus</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Toaster position="bottom-right" richColors />
        </AppLayout>
    );
}
