import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head, Link, router} from '@inertiajs/react';
import {useState} from 'react';

const money = value => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
const condition = value => value === 'GOOD' ? 'Baik' : value === 'DAMAGED' ? 'Rusak' : 'Hilang';

export default function Show({booking, invoice, payments, checkout, checkin}) {
    const [cashModalOpen, setCashModalOpen] = useState(false);
    const [submittingCash, setSubmittingCash] = useState(false);
    const [cancelModalOpen, setCancelModalOpen] = useState(false);
    const [submittingCancel, setSubmittingCancel] = useState(false);
    const checkoutOk = ['CONFIRMED', 'READY_FOR_PICKUP'].includes(booking.status);
    const extras = checkin ? [
        ['Denda keterlambatan', checkin.late_fee],
        ['Biaya kerusakan', checkin.damage_fee],
        ['Biaya bahan bakar', checkin.fuel_fee],
        ['Biaya kebersihan', checkin.cleaning_fee],
    ].filter(([, amount]) => Number(amount) > 0) : [];
    const confirmCashSettlement = () => {
        setSubmittingCash(true);
        router.post(route('admin.bookings.cash-settlement', booking.id), {}, {
            onFinish: () => setSubmittingCash(false),
        });
    };
    const confirmCancellation = () => {
        setSubmittingCancel(true);
        router.post(route('admin.bookings.cancel', booking.id), {}, {
            onFinish: () => setSubmittingCancel(false),
        });
    };

    return <AuthenticatedLayout header={booking.booking_code}>
        <Head title="Detail Booking"/>
        <div className="mx-auto grid max-w-5xl gap-5 p-6 md:grid-cols-2">
            <section className="rounded-2xl bg-white p-6 shadow"><h3 className="font-bold">Pelanggan</h3><p className="mt-3">{booking.customer.full_name}</p><p>{booking.customer.phone}</p></section>
            <section className="rounded-2xl bg-white p-6 shadow"><h3 className="font-bold">Kendaraan</h3><p className="mt-3">{booking.vehicle.brand} {booking.vehicle.model}</p><p>Status: {booking.status}</p>{checkoutOk && <div className="mt-5 flex flex-wrap gap-3"><Link href={route('admin.checkouts.create', booking.id)} className="inline-block rounded-xl bg-indigo-600 px-4 py-3 font-bold text-white">Proses checkout</Link><button type="button" onClick={() => setCancelModalOpen(true)} className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 font-bold text-red-700">Batalkan booking</button></div>}{booking.status === 'RETURN_INSPECTION' && <Link href={route('admin.returns.create', booking.id)} className="mt-5 inline-block rounded-xl bg-amber-500 px-4 py-3 font-bold text-white">Proses check-in pengembalian</Link>}</section>
            <section className="rounded-2xl bg-white p-6 shadow"><h3 className="font-bold">Invoice</h3>{invoice ? <div className="mt-3 space-y-2 text-sm"><p className="flex justify-between"><span>Total booking</span><b>{money(invoice.total)}</b></p><p className="flex justify-between"><span>Sudah dibayar</span><b className="text-emerald-700">{money(invoice.paid_amount)}</b></p><p className="flex justify-between border-t pt-2"><span>Sisa hutang</span><b className="text-amber-700">{money(invoice.balance)}</b></p>{booking.status !== 'CANCELLED' && Number(invoice.balance) > 0 && <button type="button" onClick={() => setCashModalOpen(true)} className="mt-3 w-full rounded-xl bg-emerald-600 px-4 py-3 font-bold text-white">Catat pelunasan cash · {money(invoice.balance)}</button>}<p className="text-xs font-bold text-indigo-600">{invoice.status}</p></div> : <p className="mt-3">Belum dibuat</p>}</section>
            <section className="rounded-2xl bg-white p-6 shadow"><h3 className="font-bold">Riwayat pembayaran</h3>{payments.length ? <div className="mt-3 divide-y">{payments.map(payment => <div key={payment.id} className="flex justify-between py-2 text-sm"><span><b>{payment.payment_type === 'DP' ? 'DP 10%' : 'Pelunasan'}</b><small className="block text-slate-500">{payment.method} · {payment.status}</small></span><b>{money(payment.amount)}</b></div>)}</div> : <p className="mt-3 text-sm text-slate-500">Belum ada pembayaran.</p>}</section>
            {checkout && <section className="rounded-2xl bg-white p-6 shadow md:col-span-2"><h3 className="font-bold">Checklist checkout — saat mobil diambil</h3><div className="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2"><p>Odometer awal: <b>{Number(checkout.odometer).toLocaleString('id-ID')} km</b></p><p>Bahan bakar awal: <b>{checkout.fuel_level}%</b></p></div><div className="mt-3 divide-y">{checkout.items.map(item => <p key={item.id} className="flex justify-between py-3"><span>{item.checklist_name}</span><b>{condition(item.condition_status)}</b></p>)}</div></section>}
            {checkin && <section className="rounded-2xl bg-white p-6 shadow md:col-span-2"><h3 className="font-bold">Checklist check-in — saat mobil dikembalikan</h3><div className="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-2"><p>Odometer akhir: <b>{Number(checkin.odometer).toLocaleString('id-ID')} km</b></p><p>Bahan bakar akhir: <b>{checkin.fuel_level}%</b></p></div>{extras.length > 0 && <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4"><h3 className="font-bold">Biaya tambahan</h3>{extras.map(([label, amount]) => <p key={label} className="flex justify-between py-2 text-sm"><span>{label}</span><b>{money(amount)}</b></p>)}<p className="flex justify-between border-t pt-3 font-black"><span>Total</span><span>{money(checkin.total_additional_charge)}</span></p></div>}</section>}
            <Link className="text-indigo-600" href={route('admin.bookings.index')}>← Kembali</Link>
        </div>
        {cashModalOpen && <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="cash-settlement-title">
            <div className="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-2xl text-amber-700">!</div>
                <p className="mt-5 text-sm font-bold text-amber-700">KONFIRMASI PEMBAYARAN</p>
                <h2 id="cash-settlement-title" className="mt-1 text-2xl font-black text-slate-900">Catat pelunasan cash?</h2>
                <p className="mt-3 text-sm leading-6 text-slate-600">Tindakan ini akan mencatat pembayaran tunai dan mengubah invoice menjadi lunas.</p>
                <div className="mt-5 rounded-2xl bg-slate-50 p-4"><p className="text-sm text-slate-500">Nominal pelunasan</p><p className="mt-1 text-2xl font-black text-slate-900">{money(invoice.balance)}</p></div>
                <div className="mt-6 flex gap-3"><button type="button" disabled={submittingCash} onClick={() => setCashModalOpen(false)} className="flex-1 rounded-xl border border-slate-300 px-4 py-3 font-bold text-slate-700 disabled:opacity-50">Batal</button><button type="button" disabled={submittingCash} onClick={confirmCashSettlement} className="flex-1 rounded-xl bg-emerald-600 px-4 py-3 font-bold text-white disabled:opacity-50">{submittingCash ? 'Mencatat...' : 'Ya, catat cash'}</button></div>
            </div>
        </div>}
        {cancelModalOpen && <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="cancel-booking-title">
            <div className="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-100 text-2xl text-red-700">!</div>
                <p className="mt-5 text-sm font-bold text-red-700">KONFIRMASI PEMBATALAN</p>
                <h2 id="cancel-booking-title" className="mt-1 text-2xl font-black text-slate-900">Batalkan booking ini?</h2>
                <p className="mt-3 text-sm leading-6 text-slate-600">Booking akan dibatalkan dan kendaraan kembali tersedia. Pembatalan ini tidak membuat refund pembayaran secara otomatis.</p>
                <div className="mt-5 rounded-2xl bg-slate-50 p-4 text-sm"><p className="text-slate-500">Kode booking</p><p className="mt-1 font-black text-slate-900">{booking.booking_code}</p></div>
                <div className="mt-6 flex gap-3"><button type="button" disabled={submittingCancel} onClick={() => setCancelModalOpen(false)} className="flex-1 rounded-xl border border-slate-300 px-4 py-3 font-bold text-slate-700 disabled:opacity-50">Kembali</button><button type="button" disabled={submittingCancel} onClick={confirmCancellation} className="flex-1 rounded-xl bg-red-600 px-4 py-3 font-bold text-white disabled:opacity-50">{submittingCancel ? 'Membatalkan...' : 'Ya, batalkan'}</button></div>
            </div>
        </div>}
    </AuthenticatedLayout>;
}
