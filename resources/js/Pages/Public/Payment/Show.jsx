import {Head, Link} from '@inertiajs/react';
import {useEffect, useState} from 'react';
import PublicLayout from '@/Layouts/PublicLayout';

const money = value => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;

export default function Show({booking, invoice, payment: initialPayment, selectedPaymentType}) {
    const [payment, setPayment] = useState(initialPayment);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const balance = Number(invoice?.balance ?? booking.total_amount);
    const dp = Math.ceil(Number(booking.total_amount) * .1);
    const dpPaid = Number(invoice?.paid_amount || 0) > 0;
    const paymentType = dpPaid ? 'FULL' : selectedPaymentType;
    const amount = paymentType === 'DP' ? dp : balance;
    const label = paymentType === 'DP' ? 'Pembayaran DP 10%' : 'Pelunasan';
    const isSettlement = dpPaid && selectedPaymentType === 'FULL' && balance > 0;
    const isPaid = payment?.status === 'PAID' && !isSettlement;

    useEffect(() => {
        if (!isPaid) return undefined;

        const redirect = window.setTimeout(() => window.location.assign(route('my-bookings.index')), 5000);

        return () => window.clearTimeout(redirect);
    }, [isPaid]);

    const choose = async method => {
        setLoading(true);
        setError('');

        try {
            const response = await fetch(route('payment.create', booking.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({method, payment_type: paymentType}),
            });
            const data = await response.json();

            if (!response.ok) throw new Error(data.message || 'Gagal membuat pembayaran');
            if (data.payment.qr_string) {
                window.location.assign(data.payment.qr_string);
                return;
            }

            setPayment(data.payment);
        } catch (exception) {
            setError(exception.message);
        } finally {
            setLoading(false);
        }
    };

    return <PublicLayout>
        <Head title="Pembayaran"/>
        <main className="mx-auto max-w-3xl px-6 py-12">
            <div className="rounded-3xl bg-white p-7 shadow-sm ring-1 ring-slate-200">
                {isPaid ? <div className="rounded-2xl bg-emerald-50 p-7 text-center text-emerald-950">
                    <p className="text-sm font-bold text-emerald-700">PEMBAYARAN BERHASIL</p>
                    <h1 className="mt-2 text-3xl font-black">Terima kasih!</h1>
                    <p className="mt-4 text-slate-700">{payment.payment_type === 'DP' ? 'DP berhasil dibayarkan.' : 'Pembayaran Anda sudah lunas.'}</p>
                    <p className="mt-2 text-3xl font-black text-emerald-700">{money(payment.amount)}</p>
                    <p className="mt-2 text-sm text-slate-600">Nominal pembayaran untuk booking {booking.booking_code}.</p>
                    <Link href={route('my-bookings.index')} className="mt-6 inline-block rounded-xl bg-emerald-600 px-5 py-3 font-bold text-white">Lihat My Booking</Link>
                    <p className="mt-4 text-sm text-slate-500">Anda akan diarahkan ke My Booking dalam 5 detik.</p>
                </div> : <>
                    <p className="text-sm font-bold text-indigo-600">XENDIT PAYMENT</p>
                    <h1 className="mt-2 text-3xl font-black">{isSettlement ? 'Pembayaran pelunasan' : 'Selesaikan pembayaran'}</h1>
                    <div className="mt-6 rounded-2xl bg-slate-50 p-5">
                        <div className="flex justify-between gap-4"><div><b>{booking.vehicle.brand} {booking.vehicle.model}</b><p className="mt-1 text-sm text-slate-500">{booking.booking_code}</p></div><b>{money(booking.total_amount)}</b></div>
                        {invoice && <div className="mt-4 flex justify-between border-t pt-4 text-sm"><span>Sudah dibayar: {money(invoice.paid_amount)}</span><b>Sisa tagihan: {money(balance)}</b></div>}
                    </div>
                    {error && <p className="mt-5 rounded-xl bg-red-50 p-4 text-red-700">{error}</p>}
                    {payment?.status === 'PENDING' && payment.qr_string ? <div className="mt-7 rounded-2xl bg-indigo-50 p-6 text-center"><p className="font-bold">Checkout Xendit sudah dibuat</p><p className="mt-1 text-sm text-slate-600">{payment.payment_type === 'DP' ? 'Pembayaran DP 10%' : 'Pembayaran pelunasan'} · {money(payment.amount)}</p><a href={payment.qr_string} className="mt-4 inline-block rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">Lanjutkan ke Xendit</a></div> : balance > 0 ? <div className="mt-7"><div className="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"><p className="text-sm font-bold text-indigo-700">{label.toUpperCase()}</p><p className="mt-2 text-3xl font-black text-slate-900">{money(amount)}</p><p className="mt-1 text-sm text-slate-600">Nominal ini mengikuti pilihan yang Anda buat pada form booking.</p></div><h2 className="mt-6 font-bold">Pilih metode pembayaran</h2><div className="mt-3 grid gap-3 sm:grid-cols-2"><button disabled={loading} onClick={() => choose('QRIS')} className="rounded-2xl border-2 border-slate-200 p-5 text-left hover:border-indigo-500 disabled:opacity-50"><b>QRIS</b><p className="mt-1 text-sm text-slate-500">Lanjut ke checkout aman Xendit.</p></button><button disabled={loading} onClick={() => choose('VA')} className="rounded-2xl border-2 border-slate-200 p-5 text-left hover:border-indigo-500 disabled:opacity-50"><b>Virtual Account</b><p className="mt-1 text-sm text-slate-500">Lanjut ke checkout aman Xendit.</p></button></div></div> : null}
                </>}
            </div>
        </main>
    </PublicLayout>;
}
