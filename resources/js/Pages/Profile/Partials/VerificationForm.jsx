import { useState } from 'react';

export default function VerificationForm({ customer }) {
    const [code, setCode] = useState('');
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [sent, setSent] = useState(false);
    const [loadingSend, setLoadingSend] = useState(false);
    const [loadingVerify, setLoadingVerify] = useState(false);
    const [loadingUpload, setLoadingUpload] = useState(false);
    const [file, setFile] = useState(null);
    const [phoneVerified, setPhoneVerified] = useState(!!customer?.phone_verified_at);
    const [ktpVerified, setKtpVerified] = useState(!!customer?.ktp_verified_at);

    const headers = {
        Accept: 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
    };

    const send = async () => {
        setError('');
        setMessage('');
        setLoadingSend(true);
        try {
            const r = await fetch(route('profile.whatsapp.send'), { method: 'POST', headers });
            const d = await r.json();
            if (!r.ok) {
                setError(d.message || 'Gagal mengirim kode verifikasi.');
                return;
            }
            setSent(true);
            setMessage(d.message + (d.demo_code ? ` (Kode Demo: ${d.demo_code})` : ''));
        } catch (err) {
            setError('Gagal menghubungi server: ' + err.message);
        } finally {
            setLoadingSend(false);
        }
    };

    const verify = async () => {
        if (!code || code.length !== 6) {
            setError('Masukkan 6 digit kode verifikasi WhatsApp.');
            return;
        }
        setError('');
        setMessage('');
        setLoadingVerify(true);
        try {
            const r = await fetch(route('profile.whatsapp.verify'), {
                method: 'POST',
                headers: { ...headers, 'Content-Type': 'application/json' },
                body: JSON.stringify({ code })
            });
            const d = await r.json();
            if (!r.ok) {
                setError(d.message || 'Kode verifikasi salah atau kedaluwarsa.');
                return;
            }
            setPhoneVerified(true);
            setMessage(d.message);
        } catch (err) {
            setError('Gagal memverifikasi kode: ' + err.message);
        } finally {
            setLoadingVerify(false);
        }
    };

    const upload = async () => {
        if (!file) return;
        setError('');
        setMessage('');
        setLoadingUpload(true);
        try {
            const data = new FormData();
            data.append('ktp_file', file);
            const r = await fetch(route('profile.ktp.upload'), { method: 'POST', headers, body: data });
            const d = await r.json();
            if (!r.ok) {
                setError(d.errors?.ktp_file?.[0] || d.message);
                return;
            }
            setKtpVerified(true);
            setMessage(d.message);
        } catch (err) {
            setError('Gagal mengunggah KTP: ' + err.message);
        } finally {
            setLoadingUpload(false);
        }
    };

    if (!customer) {
        return null;
    }

    return (
        <div className="max-w-xl">
            <h3 className="text-lg font-bold text-slate-900">Verifikasi Akun</h3>
            <p className="mt-1 text-sm text-slate-500">
                Verifikasikan nomor WhatsApp dan unggah identitas KTP Anda untuk menyelesaikan verifikasi profil.
            </p>

            <div className="mt-6 space-y-4">
                {/* WhatsApp Verification Card */}
                <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Verifikasi WhatsApp</span>
                            <div className="mt-1 flex items-center gap-2">
                                <b className="text-base text-slate-900">{customer.phone || 'Nomor HP Belum Diisi'}</b>
                            </div>
                        </div>

                        {phoneVerified ? (
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7"></path></svg>
                                Terverifikasi
                            </span>
                        ) : (
                            <button
                                onClick={send}
                                disabled={loadingSend || !customer.phone}
                                className="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 transition"
                            >
                                {loadingSend ? 'Mengirim...' : (sent ? 'Kirim Ulang Kode' : 'Kirim Kode WA')}
                            </button>
                        )}
                    </div>

                    {!phoneVerified && sent && (
                        <div className="mt-4 pt-4 border-t border-slate-100">
                            <label className="block text-xs font-medium text-slate-600 mb-1.5">
                                Masukkan 6 Digit Kode yang Dikirim ke WhatsApp:
                            </label>
                            <div className="flex gap-2">
                                <input
                                    type="text"
                                    maxLength={6}
                                    value={code}
                                    onChange={e => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                                    placeholder="Contoh: 123456"
                                    className="w-full rounded-xl border-slate-200 font-mono tracking-widest text-center text-lg font-bold text-slate-900 focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <button
                                    onClick={verify}
                                    disabled={loadingVerify || code.length !== 6}
                                    className="rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50 transition whitespace-nowrap"
                                >
                                    {loadingVerify ? 'Memeriksa...' : 'Verifikasi'}
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                {/* KTP Verification Card */}
                <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">Verifikasi KTP</span>
                            <p className="mt-1 text-sm text-slate-600">
                                {ktpVerified ? 'Dokumen KTP berhasil diunggah dan terverifikasi.' : 'Unggah foto/file KTP Anda (Maks 5MB)'}
                            </p>
                        </div>
                        {ktpVerified && (
                            <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200">
                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7"></path></svg>
                                Terverifikasi
                            </span>
                        )}
                    </div>

                    {!ktpVerified && (
                        <div className="mt-4 pt-4 border-t border-slate-100 flex flex-wrap items-center gap-3">
                            <input
                                type="file"
                                accept="image/jpeg,image/png,application/pdf"
                                onChange={e => setFile(e.target.files?.[0] || null)}
                                className="text-xs text-slate-600 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold hover:file:bg-slate-200"
                            />
                            <button
                                onClick={upload}
                                disabled={!file || loadingUpload}
                                className="rounded-xl bg-slate-800 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-slate-700 disabled:opacity-50 transition"
                            >
                                {loadingUpload ? 'Mengunggah...' : 'Upload KTP'}
                            </button>
                        </div>
                    )}
                </div>

                {/* Notifications */}
                {message && (
                    <div className="rounded-xl bg-emerald-50 border border-emerald-200 p-3.5 text-sm font-semibold text-emerald-800 flex items-center gap-2">
                        <svg className="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{message}</span>
                    </div>
                )}
                {error && (
                    <div className="rounded-xl bg-red-50 border border-red-200 p-3.5 text-sm font-semibold text-red-800 flex items-center gap-2">
                        <svg className="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>{error}</span>
                    </div>
                )}
            </div>
        </div>
    );
}

