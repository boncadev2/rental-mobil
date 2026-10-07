import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {Head} from '@inertiajs/react';

const money = value => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;

export default function Dashboard({metrics = {}}) {
    const cards = [
        ['Total booking', metrics.bookings ?? 0],
        ['Pendapatan', money(metrics.revenue)],
        ['Piutang aktif', money(metrics.receivables), `${metrics.receivableCount ?? 0} booking belum lunas`],
        ['Mobil digunakan', metrics.vehiclesInUse ?? 0],
        ['Maintenance alert', metrics.maintenanceDue ?? 0],
    ];

    return <AuthenticatedLayout header="Dashboard"><Head title="Dashboard"/><div className="grid gap-5 md:grid-cols-2 xl:grid-cols-5">{cards.map(([title, value, caption], index) => <div key={title} className={`rounded-2xl p-6 shadow-sm ${index === 0 ? 'bg-indigo-600 text-white' : title === 'Piutang aktif' ? 'border border-amber-200 bg-amber-50' : 'bg-white'}`}><p className="text-sm opacity-70">{title}</p><p className="mt-3 text-3xl font-bold">{value}</p>{caption && <p className="mt-2 text-xs opacity-70">{caption}</p>}</div>)}</div><div className="mt-6 rounded-2xl bg-white p-7 shadow-sm"><h3 className="font-bold">Ringkasan operasional</h3><p className="mt-2 text-sm text-slate-500">Piutang aktif adalah sisa tagihan booking yang belum lunas dan tidak termasuk booking dibatalkan.</p></div></AuthenticatedLayout>;
}
