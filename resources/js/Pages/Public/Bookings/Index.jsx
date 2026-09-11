import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

const money = (value) => `Rp ${Number(value).toLocaleString('id-ID')}`;

export default function Index({ bookings }) {
    return <PublicLayout><Head title="Booking Saya" /><main className="mx-auto max-w-6xl px-6 py-10"><h1 className="text-3xl font-bold">Booking Saya</h1><p className="mt-2 text-slate-500">Pantau seluruh booking yang dibuat menggunakan akun Anda.</p><div className="mt-7 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">{bookings.data.length ? bookings.data.map((booking) => <div key={booking.id} className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-5 last:border-0"><div><p className="font-bold text-slate-900">{booking.booking_code} · {booking.vehicle.brand} {booking.vehicle.model}</p><p className="mt-1 text-sm text-slate-500">{new Date(booking.start_datetime).toLocaleDateString('id-ID')} – {new Date(booking.end_datetime).toLocaleDateString('id-ID')}</p></div><div className="text-right"><p className="font-bold">{money(booking.total_amount)}</p><span className="mt-1 inline-block rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{booking.status}</span></div></div>) : <div className="p-10 text-center text-slate-500">Belum ada booking. <Link className="font-semibold text-blue-600" href={route('cars.index')}>Pilih mobil</Link></div>}</div></main></PublicLayout>;
}
