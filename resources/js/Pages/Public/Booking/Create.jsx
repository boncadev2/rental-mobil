import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';

const money = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
const toLocalDateInput = (date) => {
    const local = new Date(date.getTime() - (date.getTimezoneOffset() * 60_000));
    return local.toISOString().slice(0, 10);
};
const displayDate = (value) => new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

export default function Create({ vehicle, customerVerified, customerPhone }) {
    const today = useMemo(() => toLocalDateInput(new Date()), []);
    const { auth } = usePage().props;
    const [form, setForm] = useState({
        full_name: auth.user?.name || '', email: auth.user?.email || '', phone: customerPhone || '',
        start_datetime: today, end_datetime: today, pickup_location: '',
        ktp_file: null, use_driver: false, driver_type: '', payment_type: 'DP', customer_notes: '',
    });
    const [errors, setErrors] = useState({});
    const [result, setResult] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const [availability, setAvailability] = useState(null);
    const minStartDate = today;
    const minEndDate = form.start_datetime || minStartDate;

    useEffect(() => {
        if (!form.start_datetime || !form.end_datetime || form.end_datetime < form.start_datetime) {
            setAvailability(null);
            return undefined;
        }

        let active = true;
        setAvailability(null);
        fetch(route('booking.availability', { vehicle: vehicle.id, _query: { start_date: form.start_datetime, end_date: form.end_datetime } }), { headers: { Accept: 'application/json' } })
            .then((response) => response.ok ? response.json() : null)
            .then((data) => { if (active && data) setAvailability(data); })
            .catch(() => { if (active) setAvailability(null); });

        return () => { active = false; };
    }, [form.start_datetime, form.end_datetime, vehicle.id]);

    const driverOptions = useMemo(() => [
        { value: 'IN_CITY', label: `Driver dalam kota — ${money(vehicle.driver_daily_price)}/hari` },
        { value: 'OUT_OF_CITY', label: `Driver luar kota — ${money(Number(vehicle.driver_daily_price) * 1.5)}/hari` },
    ], [vehicle.driver_daily_price]);
    const estimate = useMemo(() => {
        if (!form.start_datetime || !form.end_datetime || form.end_datetime < form.start_datetime) return null;
        const start = new Date(`${form.start_datetime}T00:00:00`);
        const end = new Date(`${form.end_datetime}T00:00:00`);
        const days = Math.floor((end - start) / 86_400_000) + 1;
        const rental = Number(vehicle.daily_price) * days;
        const driverDaily = form.use_driver ? Number(vehicle.driver_daily_price) * (form.driver_type === 'OUT_OF_CITY' ? 1.5 : 1) : 0;
        const driver = driverDaily * days;
        return { days, rental, driver, driverDaily, subtotal: rental + driver };
    }, [form.start_datetime, form.end_datetime, form.use_driver, form.driver_type, vehicle.daily_price, vehicle.driver_daily_price]);

    const update = (key, value) => setForm((current) => ({ ...current, [key]: value }));
    const updateStartDateTime = (value) => {
        setForm((current) => ({
            ...current,
            start_datetime: value,
            end_datetime: current.end_datetime && current.end_datetime < value ? '' : current.end_datetime,
        }));
    };
    const submit = async (event) => {
        event.preventDefault();
        setSubmitting(true); setErrors({}); setResult(null);
        try {
            const payload = new FormData();
            Object.entries({ ...form, use_driver: form.use_driver ? '1' : '0', vehicle_id: vehicle.id }).forEach(([key, value]) => { if (value !== null) payload.append(key, value); });
            const response = await fetch(route('booking.store'), { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content }, body: payload });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) { setErrors(data.errors || { form: [data.message || 'Booking belum dapat dibuat. Silakan muat ulang halaman lalu coba lagi.'] }); return; }
        window.location.assign(data.payment_url);
        } catch { setErrors({ form: ['Terjadi gangguan koneksi. Silakan muat ulang halaman lalu coba kembali.'] }); } finally { setSubmitting(false); }
    };

    return <PublicLayout>
        <Head title={`Booking ${vehicle.name}`} />
        <main className="mx-auto max-w-5xl px-4 py-10 sm:px-6">
            <Link href={route('cars.show', vehicle.id)} className="text-sm font-semibold text-blue-600">← Kembali ke detail mobil</Link>
            <div className="mt-4 grid gap-6 lg:grid-cols-[.85fr_1.15fr]">
                <aside className="rounded-3xl bg-slate-950 p-6 text-white shadow-xl">
                    <p className="text-sm text-blue-200">Mobil pilihan</p><h1 className="mt-2 text-2xl font-bold">{vehicle.brand} {vehicle.model}</h1>
                    <p className="mt-2 text-slate-300">{vehicle.category?.name} · {vehicle.seat_capacity} kursi · {vehicle.transmission}</p>
                    <div className="mt-6 border-t border-slate-700 pt-5"><p className="text-sm text-slate-300">Tarif sewa mobil</p><p className="text-2xl font-bold">{money(vehicle.daily_price)}<span className="text-sm font-normal text-slate-300"> / hari</span></p></div>
                    <div className="mt-5 rounded-2xl bg-white/10 p-4 text-sm text-slate-200">
                        <p className="font-semibold text-white">Estimasi biaya</p>
                        {estimate ? <div className="mt-3 space-y-2">
                            <div className="flex justify-between gap-3"><span>{estimate.days} hari × {money(vehicle.daily_price)}</span><b>{money(estimate.rental)}</b></div>
                            {form.use_driver && <div className="flex justify-between gap-3"><span>Driver {estimate.days} hari<br/><small className="text-blue-200">{money(estimate.driverDaily)}/hari</small></span><b>{money(estimate.driver)}</b></div>}
                            <div className="flex justify-between gap-3 border-t border-white/20 pt-2"><span>Subtotal sewa</span><b>{money(estimate.subtotal)}</b></div>
                            <div className="flex justify-between gap-3 border-t border-white/20 pt-3 text-base text-white"><span>Total estimasi</span><b>{money(estimate.subtotal)}</b></div>
                            <div className="flex justify-between gap-3 text-blue-100"><span>{form.payment_type === 'DP' ? 'DP 10% yang dibayar sekarang' : 'Pelunasan yang dibayar sekarang'}</span><b>{money(form.payment_type === 'DP' ? Math.ceil(estimate.subtotal * .1) : estimate.subtotal)}</b></div>
                        </div> : <p className="mt-2 leading-6">Pilih tanggal mulai dan selesai untuk melihat total biaya.</p>}
                    </div>
                </aside>
                <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 className="text-2xl font-bold text-slate-900">Lengkapi data booking</h2><p className="mt-1 text-sm text-slate-500">Pilih jadwal dan layanan driver sesuai perjalanan Anda.</p>
                    {result && <div className="mt-5 rounded-2xl bg-emerald-50 p-4 text-emerald-800">Booking <b>{result.booking_code}</b> berhasil dibuat. Total: <b>{money(result.total_amount)}</b>.</div>}
                    {errors.form && <div className="mt-5 rounded-2xl bg-red-50 p-4 text-red-700">{errors.form[0]}</div>}
                    <form onSubmit={submit} className="mt-6 grid gap-4 sm:grid-cols-2">
                        {[['full_name','Nama lengkap','text'],['email','Email','email'],['phone','Nomor WhatsApp','tel'],['pickup_location','Lokasi penjemputan','text']].map(([key,label,type]) => <label key={key} className="text-sm font-semibold text-slate-700">{label}<input required={key !== 'email'} type={type} value={form[key]} onChange={e=>update(key,e.target.value)} className="mt-1.5 w-full rounded-xl border-slate-200" />{errors[key] && <small className="text-red-600">{errors[key][0]}</small>}</label>)}
                        {customerVerified ? <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><b>✓ Akun terverifikasi</b><p className="mt-1">KTP sudah terverifikasi, jadi tidak perlu diunggah ulang.</p></div> : <label className="text-sm font-semibold text-slate-700">Upload KTP <span className="text-red-600">*</span><input required type="file" accept="image/jpeg,image/png,application/pdf" onChange={e=>update('ktp_file',e.target.files?.[0] || null)} className="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white text-sm file:mr-4 file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700" />{errors.ktp_file && <small className="text-red-600">{errors.ktp_file[0]}</small>}<span className="mt-1 block text-xs font-normal text-slate-500">Wajib diisi · JPG, PNG, atau PDF · maksimal 5 MB · disimpan privat.</span></label>}
                        <label className="text-sm font-semibold text-slate-700">Tanggal mulai sewa<input required type="date" min={minStartDate} value={form.start_datetime} onChange={e=>updateStartDateTime(e.target.value)} className="mt-1.5 w-full rounded-xl border-slate-200" />{errors.start_datetime && <small className="text-red-600">{errors.start_datetime[0]}</small>}</label>
                        <label className="text-sm font-semibold text-slate-700">Tanggal selesai sewa<input required type="date" min={minEndDate} value={form.end_datetime} onChange={e=>update('end_datetime',e.target.value)} className="mt-1.5 w-full rounded-xl border-slate-200" />{errors.end_datetime && <small className="text-red-600">{errors.end_datetime[0]}</small>}</label>
                        {availability?.available === false && <div className="sm:col-span-2 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><b>Mobil tidak tersedia pada tanggal pilihan Anda.</b><p className="mt-1">Sudah dibooking dari {displayDate(availability.booking.start_datetime)} s/d {displayDate(availability.booking.end_datetime)}. Silakan pilih tanggal lain.</p></div>}
                        <div className="sm:col-span-2 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            <label className="flex cursor-pointer items-center gap-3 font-semibold text-slate-800"><input type="checkbox" checked={form.use_driver} onChange={e=>{ update('use_driver',e.target.checked); update('driver_type',e.target.checked ? 'IN_CITY' : ''); }} className="rounded border-slate-300 text-blue-600" />Gunakan driver</label>
                            {form.use_driver && <label className="mt-4 block text-sm font-semibold text-slate-700">Jenis perjalanan<select required value={form.driver_type} onChange={e=>update('driver_type',e.target.value)} className="mt-1.5 w-full rounded-xl border-slate-200 bg-white"><option value="">Pilih layanan driver</option>{driverOptions.map(option=><option key={option.value} value={option.value}>{option.label}</option>)}</select>{errors.driver_type && <small className="text-red-600">{errors.driver_type[0]}</small>}<span className="mt-2 block font-normal text-slate-500">Luar kota dihitung 1,5× dari tarif driver reguler.</span></label>}
                        </div>
                        <div className="sm:col-span-2 rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                            <p className="font-semibold text-slate-800">Pilih pembayaran</p><p className="mt-1 text-sm text-slate-500">Tentukan jenis pembayaran sebelum booking dibuat.</p>
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                <label className={`cursor-pointer rounded-xl border-2 bg-white p-4 ${form.payment_type === 'DP' ? 'border-indigo-600' : 'border-slate-200'}`}><input type="radio" name="payment_type" value="DP" checked={form.payment_type === 'DP'} onChange={e=>update('payment_type',e.target.value)} className="mr-2"/><b>Bayar DP 10%</b><span className="mt-1 block text-sm font-normal text-slate-500">Bayar 10% dari total sekarang, sisa dapat dilunasi kemudian.</span></label>
                                <label className={`cursor-pointer rounded-xl border-2 bg-white p-4 ${form.payment_type === 'FULL' ? 'border-indigo-600' : 'border-slate-200'}`}><input type="radio" name="payment_type" value="FULL" checked={form.payment_type === 'FULL'} onChange={e=>update('payment_type',e.target.value)} className="mr-2"/><b>Bayar lunas</b><span className="mt-1 block text-sm font-normal text-slate-500">Bayar seluruh total tagihan sekarang.</span></label>
                            </div>{errors.payment_type && <small className="text-red-600">{errors.payment_type[0]}</small>}
                        </div>
                        <label className="sm:col-span-2 text-sm font-semibold text-slate-700">Catatan (opsional)<textarea value={form.customer_notes} onChange={e=>update('customer_notes',e.target.value)} rows="3" className="mt-1.5 w-full rounded-xl border-slate-200" /></label>
                        <button disabled={submitting || availability?.available === false} className="sm:col-span-2 rounded-xl bg-blue-600 px-5 py-3.5 font-bold text-white transition hover:bg-blue-700 disabled:opacity-60">{submitting ? 'Memproses...' : 'Buat booking'}</button>
                    </form>
                </section>
            </div>
        </main>
    </PublicLayout>;
}
