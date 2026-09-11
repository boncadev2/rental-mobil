import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function LogoutConfirmation() {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        const show = () => setOpen(true);
        window.addEventListener('logout-confirmation', show);

        return () => window.removeEventListener('logout-confirmation', show);
    }, []);

    if (!open) return null;

    const logout = () => {
        setOpen(false);
        router.post(route('logout'));
    };

    return <div role="dialog" aria-modal="true" aria-labelledby="logout-title" className="fixed inset-0 z-[100] grid place-items-center p-4"><button type="button" aria-label="Tutup" onClick={() => setOpen(false)} className="absolute inset-0 cursor-default bg-slate-950/50 backdrop-blur-sm" /><section className="relative w-full max-w-sm rounded-3xl bg-white p-7 shadow-2xl"><div className="grid h-12 w-12 place-items-center rounded-2xl bg-red-50 text-2xl">↪</div><h2 id="logout-title" className="mt-5 text-xl font-black text-slate-900">Keluar dari akun?</h2><p className="mt-2 text-sm leading-6 text-slate-500">Anda perlu masuk kembali untuk mengakses data booking dan profil.</p><div className="mt-7 flex gap-3"><button type="button" onClick={() => setOpen(false)} className="flex-1 rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Batal</button><button type="button" data-logout-confirmation-action="true" onClick={logout} className="flex-1 rounded-xl bg-red-600 px-4 py-3 text-sm font-bold text-white hover:bg-red-700">Keluar</button></div></section></div>;
}
