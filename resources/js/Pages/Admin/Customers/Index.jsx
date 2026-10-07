import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Dialog } from '@headlessui/react';

export default function Index({ customers, search }) {
    const [query, setQuery] = useState(search || '');
    const [selectedCustomer, setSelectedCustomer] = useState(null);

    const submit = (event) => { 
        event.preventDefault(); 
        router.get(route('admin.customers.index'), { search: query || undefined }, { preserveState: true, replace: true }); 
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Pelanggan</h2>}>
            <Head title="Pelanggan" />
            
            <div className="mx-auto max-w-7xl p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black">Daftar pelanggan</h1>
                        <p className="mt-1 text-slate-500">Data kontak, alamat, KTP, dan jumlah booking pelanggan.</p>
                    </div>
                    <form onSubmit={submit} className="flex gap-2">
                        <input 
                            value={query} 
                            onChange={e => setQuery(e.target.value)} 
                            placeholder="Cari nama, email, telepon" 
                            className="rounded-xl border-slate-200" 
                        />
                        <button className="rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white">Cari</button>
                    </form>
                </div>
                
                <div className="mt-7 overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-6 py-4">Pelanggan</th>
                                <th className="px-6 py-4">Kontak</th>
                                <th className="px-6 py-4">Akun</th>
                                <th className="px-6 py-4 text-center">Booking</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {customers.data.length ? customers.data.map(customer => (
                                <tr key={customer.id} className="border-t border-slate-100">
                                    <td className="px-6 py-4">
                                        <b>{customer.full_name}</b>
                                        <p className="mt-1 text-xs text-slate-500">{customer.customer_code}</p>
                                    </td>
                                    <td className="px-6 py-4">
                                        {customer.phone}
                                        <p className="mt-1 text-slate-500">{customer.email || '—'}</p>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className={`rounded-full px-3 py-1 text-xs font-bold ${customer.user_id ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                                            {customer.user_id ? 'Login' : 'Tamu'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-center font-bold">{customer.bookings_count}</td>
                                    <td className="px-6 py-4">
                                        <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{customer.status}</span>
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <button 
                                            onClick={() => setSelectedCustomer(customer)}
                                            className="text-indigo-600 font-semibold hover:text-indigo-800"
                                        >
                                            Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="6" className="px-6 py-12 text-center text-slate-500">Belum ada data pelanggan.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Modal Detail Pelanggan */}
            <Dialog open={selectedCustomer !== null} onClose={() => setSelectedCustomer(null)} className="relative z-50">
                <div className="fixed inset-0 bg-black/30 backdrop-blur-sm" aria-hidden="true" />
                <div className="fixed inset-0 flex items-center justify-center p-4">
                    <Dialog.Panel className="mx-auto w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                        <Dialog.Title className="text-xl font-bold mb-4">Detail Pelanggan</Dialog.Title>
                        
                        {selectedCustomer && (
                            <div className="space-y-4">
                                <div>
                                    <p className="text-sm text-slate-500 font-semibold">Nama Lengkap</p>
                                    <p className="text-gray-900">{selectedCustomer.full_name} ({selectedCustomer.customer_code})</p>
                                </div>
                                <div>
                                    <p className="text-sm text-slate-500 font-semibold">Email & Telepon</p>
                                    <p className="text-gray-900">{selectedCustomer.email || '-'} / {selectedCustomer.phone}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-slate-500 font-semibold">Alamat Lengkap</p>
                                    <div className="mt-1 p-3 bg-slate-50 rounded-xl border border-slate-100 text-gray-700">
                                        {selectedCustomer.address || <em className="text-slate-400">Alamat belum diisi.</em>}
                                    </div>
                                </div>
                                <div>
                                    <p className="text-sm text-slate-500 font-semibold mb-1">Foto KTP / Identitas</p>
                                    {selectedCustomer.ktp_file_path ? (
                                        <a 
                                            href={route('admin.customers.ktp', selectedCustomer.id)}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-lg font-semibold border border-emerald-200 hover:bg-emerald-100 transition-colors"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" className="w-5 h-5">
                                              <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                            Lihat Dokumen KTP
                                        </a>
                                    ) : (
                                        <div className="px-4 py-2 bg-slate-50 text-slate-500 rounded-lg inline-block border border-slate-100">
                                            KTP belum diunggah.
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="mt-8 flex justify-end">
                            <button 
                                onClick={() => setSelectedCustomer(null)}
                                className="px-5 py-2 bg-slate-900 text-white font-semibold rounded-xl hover:bg-slate-800"
                            >
                                Tutup
                            </button>
                        </div>
                    </Dialog.Panel>
                </div>
            </Dialog>
        </AuthenticatedLayout>
    );
}
