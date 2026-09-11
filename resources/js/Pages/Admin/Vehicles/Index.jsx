import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ vehicles }) {
    const remove = (vehicle) => { if (confirm(`Hapus ${vehicle.brand} ${vehicle.model}?`)) router.delete(route('admin.vehicles.destroy', vehicle.id)); };
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold">Kendaraan</h2>}><Head title="Kendaraan" />
        <div className="mx-auto max-w-7xl p-6"><div className="mb-5 flex justify-end"><Link className="rounded bg-indigo-600 px-4 py-2 text-white" href={route('admin.vehicles.create')}>Tambah kendaraan</Link></div>
        <div className="overflow-hidden rounded bg-white shadow"><table className="w-full text-left text-sm"><thead className="bg-gray-50 text-gray-600"><tr><th className="p-4">Kode</th><th>Mobil</th><th>Kategori</th><th>Status</th><th>Harga/hari</th><th></th></tr></thead><tbody>{vehicles.data.map(v=><tr key={v.id} className="border-t"><td className="p-4">{v.code}</td><td>{v.brand} {v.model}</td><td>{v.category.name}</td><td>{v.status}</td><td>Rp {Number(v.daily_price).toLocaleString('id-ID')}</td><td className="space-x-3"><Link className="text-indigo-600" href={route('admin.vehicles.edit',v.id)}>Edit</Link><button onClick={()=>remove(v)} className="text-red-600">Hapus</button></td></tr>)}</tbody></table>{vehicles.data.length===0&&<p className="p-6 text-gray-500">Belum ada kendaraan.</p>}</div></div>
    </AuthenticatedLayout>;
}
