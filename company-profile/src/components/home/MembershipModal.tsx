"use client";

import React, { useState } from 'react';
import { 
  X, CheckCircle, Phone, Mail, MapPin, User, 
  ArrowRight, Loader2, Lock
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

interface MembershipModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: (member: any) => void;
}

const getEcomApiUrl = () => {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://api-sminventory/api/company-profile';
  return apiUrl.replace('/company-profile', '/v1');
};

export default function MembershipModal({ isOpen, onClose, onSuccess }: MembershipModalProps) {
  const [activeTab, setActiveTab] = useState<'login' | 'register' | 'forgot'>('login');
  
  // Form states
  const [loginPhone, setLoginPhone] = useState('');
  const [loginPassword, setLoginPassword] = useState('');
  const [regName, setRegName] = useState('');
  const [regPhone, setRegPhone] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regAddress, setRegAddress] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [forgotPhone, setForgotPhone] = useState('');
  const [forgotOtp, setForgotOtp] = useState('');
  const [forgotNewPassword, setForgotNewPassword] = useState('');
  const [forgotStep, setForgotStep] = useState<1 | 2>(1);

  // UI state
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  if (!isOpen) return null;

  const handleSendOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);
    try {
      const res = await fetch(`${getEcomApiUrl()}/ecommerce/members/forgot-password`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ phone: forgotPhone })
      });
      const data = await res.json();
      if (res.ok) {
        setSuccessMsg(data.message || 'OTP terkirim!');
        setTimeout(() => setSuccessMsg(null), 3000);
        setForgotStep(2);
      } else {
        setError(data.message || 'Gagal mengirim OTP.');
      }
    } catch (err: any) {
      setError('Terjadi kesalahan jaringan.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleResetSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);
    try {
      const res = await fetch(`${getEcomApiUrl()}/ecommerce/members/reset-password`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          phone: forgotPhone,
          otp: forgotOtp,
          password: forgotNewPassword,
        })
      });
      const data = await res.json();
      if (res.ok) {
        setSuccessMsg(data.message || 'Password berhasil direset!');
        setTimeout(() => setSuccessMsg(null), 3000);
        setForgotOtp('');
        setForgotNewPassword('');
        setForgotStep(1);
        setLoginPhone(forgotPhone);
        setActiveTab('login');
      } else {
        setError(data.message || 'Gagal mereset kata sandi.');
      }
    } catch (err: any) {
      setError('Terjadi kesalahan jaringan.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleLoginSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      const res = await fetch(`${getEcomApiUrl()}/ecommerce/members/login`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          phone: loginPhone,
          password: loginPassword,
        })
      });
      const data = await res.json();
      if (res.ok && data.member) {
        setLoginPassword('');
        onSuccess(data.member);
        onClose();
      } else {
        setError(data.message || 'Nomor WhatsApp atau sandi salah.');
      }
    } catch (err: any) {
      setError('Terjadi kesalahan jaringan.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleRegisterSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      const res = await fetch(`${getEcomApiUrl()}/ecommerce/members`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          name: regName,
          phone: regPhone,
          email: regEmail || null,
          address: regAddress || null,
          password: regPassword,
        })
      });
      const data = await res.json();
      if (res.ok && data.member) {
        setRegPassword('');
        onSuccess(data.member);
        onClose();
      } else {
        setError(data.message || 'Gagal mendaftar. Silakan coba lagi.');
      }
    } catch (err: any) {
      setError('Terjadi kesalahan jaringan.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <AnimatePresence>
      <motion.div 
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm"
      >
        <motion.div 
          initial={{ scale: 0.95, opacity: 0, y: 20 }}
          animate={{ scale: 1, opacity: 1, y: 0 }}
          exit={{ scale: 0.95, opacity: 0, y: 20 }}
          className="bg-white rounded-none sm:rounded-3xl shadow-2xl max-w-lg w-full h-full sm:h-[85vh] flex flex-col overflow-hidden border border-slate-100"
        >
          {/* Header */}
          <div className="relative p-6 border-b border-slate-100 flex justify-between items-center bg-white flex-shrink-0">
            <div>
              <h3 className="text-xl font-extrabold text-slate-800 tracking-tight">
                Member Toserba Selamat
              </h3>
              <p className="text-xs text-slate-500 mt-1">
                Dapatkan keuntungan koin belanja & potongan harga eksklusif.
              </p>
            </div>
            <button 
              onClick={onClose}
              className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-200 transition-all"
            >
              <X size={18} />
            </button>
          </div>

          {/* Content Area */}
          <div className="flex-grow overflow-y-auto p-6 relative">
            {error && (
              <div className="mb-4 p-3.5 bg-red-50 text-red-600 rounded-xl text-sm font-semibold border border-red-100 flex items-center gap-2">
                <span className="w-1.5 h-1.5 rounded-full bg-red-600 flex-shrink-0" />
                {error}
              </div>
            )}

            {successMsg && (
              <div className="mb-4 p-3.5 bg-emerald-50 text-emerald-700 rounded-xl text-sm font-semibold border border-emerald-100 flex items-center gap-2">
                <CheckCircle className="text-emerald-600" size={16} />
                {successMsg}
              </div>
            )}

            <div className="space-y-6">
              {activeTab !== 'forgot' && (
                <>
                  {/* Tab Selector */}
                  <div className="flex bg-slate-100 p-1.5 rounded-2xl">
                    <button
                      onClick={() => { setActiveTab('login'); setError(null); }}
                      className={`flex-1 py-2.5 text-sm font-bold rounded-xl transition-all ${
                        activeTab === 'login' 
                          ? 'bg-white text-primary shadow-sm' 
                          : 'text-slate-500 hover:text-slate-800'
                      }`}
                    >
                      Masuk Member
                    </button>
                    <button
                      onClick={() => { setActiveTab('register'); setError(null); }}
                      className={`flex-1 py-2.5 text-sm font-bold rounded-xl transition-all ${
                        activeTab === 'register' 
                          ? 'bg-white text-primary shadow-sm' 
                          : 'text-slate-500 hover:text-slate-800'
                      }`}
                    >
                      Daftar Baru
                    </button>
                  </div>

                  {activeTab === 'login' ? (
                    /* Login Form */
                    <form onSubmit={handleLoginSubmit} className="space-y-4">
                      <div className="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-xs text-blue-800 leading-relaxed">
                        Masukkan nomor WhatsApp yang terdaftar untuk masuk dan melihat poin & riwayat belanja.
                      </div>
                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">No. WhatsApp Member *</label>
                        <div className="relative">
                          <input
                            type="tel"
                            required
                            value={loginPhone}
                            onChange={(e) => setLoginPhone(e.target.value)}
                            placeholder="Contoh: 08123456789"
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800"
                          />
                          <Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Kata Sandi *</label>
                        <div className="relative">
                          <input
                            type="password"
                            required
                            value={loginPassword}
                            onChange={(e) => setLoginPassword(e.target.value)}
                            placeholder="Masukkan kata sandi Anda"
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800"
                          />
                          <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                        <div className="flex justify-end mt-1">
                          <button
                            type="button"
                            onClick={() => {
                              setForgotPhone(loginPhone);
                              setActiveTab('forgot');
                              setForgotStep(1);
                              setError(null);
                            }}
                            className="text-xs font-bold text-primary hover:underline"
                          >
                            Lupa Kata Sandi?
                          </button>
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-3.5 bg-primary text-white font-extrabold rounded-2xl hover:bg-primary/95 hover:shadow-lg active:scale-[0.98] transition-all text-sm mt-4 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-primary/10"
                      >
                        {isLoading ? (
                          <>
                            <Loader2 className="animate-spin" size={16} />
                            Memproses...
                          </>
                        ) : (
                          <>
                            Masuk Sekarang
                            <ArrowRight size={16} />
                          </>
                        )}
                      </button>
                    </form>
                  ) : (
                    /* Register Form */
                    <form onSubmit={handleRegisterSubmit} className="space-y-4">
                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nama Lengkap *</label>
                        <div className="relative">
                          <input
                            type="text"
                            required
                            value={regName}
                            onChange={(e) => setRegName(e.target.value)}
                            placeholder="Nama lengkap sesuai KTP"
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800"
                          />
                          <User className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">No. WhatsApp *</label>
                        <div className="relative">
                          <input
                            type="tel"
                            required
                            value={regPhone}
                            onChange={(e) => setRegPhone(e.target.value)}
                            placeholder="Contoh: 08123456789"
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800"
                          />
                          <Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Alamat Email (Opsional)</label>
                        <div className="relative">
                          <input
                            type="email"
                            value={regEmail}
                            onChange={(e) => setRegEmail(e.target.value)}
                            placeholder="email@anda.com"
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800"
                          />
                          <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Alamat Lengkap (Opsional)</label>
                        <div className="relative">
                          <textarea
                            value={regAddress}
                            onChange={(e) => setRegAddress(e.target.value)}
                            placeholder="Alamat lengkap untuk pengiriman e-commerce"
                            rows={2}
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800 resize-none"
                          />
                          <MapPin className="absolute left-3.5 top-4 text-slate-400" size={16} />
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Buat Kata Sandi *</label>
                        <div className="relative">
                          <input
                            type="password"
                            required
                            value={regPassword}
                            onChange={(e) => setRegPassword(e.target.value)}
                            placeholder="Minimal 6 karakter"
                            minLength={6}
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800"
                          />
                          <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-3 bg-primary text-white font-extrabold rounded-xl hover:bg-primary/90 hover:shadow-lg active:scale-95 transition-all text-sm mt-4 disabled:opacity-50 flex items-center justify-center gap-2"
                      >
                        {isLoading ? (
                          <>
                            <Loader2 className="animate-spin" size={16} />
                            Mendaftarkan...
                          </>
                        ) : (
                          'Daftar Member Baru'
                        )}
                      </button>
                    </form>
                  )}
                </>
              )}

              {activeTab === 'forgot' && (
                <div className="space-y-4">
                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={() => {
                        setActiveTab('login');
                        setError(null);
                      }}
                      className="text-xs font-bold text-primary hover:underline flex items-center gap-1"
                    >
                      &larr; Kembali ke Login
                    </button>
                  </div>

                  {forgotStep === 1 ? (
                    <form onSubmit={handleSendOtp} className="space-y-4">
                      <div className="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-xs text-amber-800 leading-relaxed">
                        Masukkan nomor WhatsApp Anda. Kami akan mengirimkan 6-digit kode OTP untuk mereset kata sandi Anda.
                      </div>
                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">No. WhatsApp Member *</label>
                        <div className="relative">
                          <input
                            type="tel"
                            required
                            value={forgotPhone}
                            onChange={(e) => setForgotPhone(e.target.value)}
                            placeholder="Contoh: 08123456789"
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800"
                          />
                          <Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-3.5 bg-primary text-white font-extrabold rounded-2xl hover:bg-primary/95 hover:shadow-lg active:scale-[0.98] transition-all text-sm mt-4 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-primary/10"
                      >
                        {isLoading ? (
                          <>
                            <Loader2 className="animate-spin" size={16} />
                            Mengirim OTP...
                          </>
                        ) : (
                          <>
                            Kirim Kode OTP via WA
                            <ArrowRight size={16} />
                          </>
                        )}
                      </button>
                    </form>
                  ) : (
                    <form onSubmit={handleResetSubmit} className="space-y-4">
                      <div className="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 text-xs text-emerald-800 leading-relaxed">
                        Kode OTP telah dikirim ke WhatsApp Anda. Silakan masukkan kode tersebut dan buat kata sandi baru.
                      </div>
                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Kode OTP (6 Digit) *</label>
                        <div className="relative">
                          <input
                            type="text"
                            required
                            maxLength={6}
                            value={forgotOtp}
                            onChange={(e) => setForgotOtp(e.target.value)}
                            placeholder="Masukkan 6-digit OTP"
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800 text-center tracking-widest font-bold"
                          />
                          <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Kata Sandi Baru *</label>
                        <div className="relative">
                          <input
                            type="password"
                            required
                            value={forgotNewPassword}
                            onChange={(e) => setForgotNewPassword(e.target.value)}
                            placeholder="Minimal 6 karakter"
                            minLength={6}
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-sm text-slate-800"
                          />
                          <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-3.5 bg-primary text-white font-extrabold rounded-2xl hover:bg-primary/95 hover:shadow-lg active:scale-[0.98] transition-all text-sm mt-4 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-primary/10"
                      >
                        {isLoading ? (
                          <>
                            <Loader2 className="animate-spin" size={16} />
                            Mereset Kata Sandi...
                          </>
                        ) : (
                          'Reset & Simpan Kata Sandi'
                        )}
                      </button>
                    </form>
                  )}
                </div>
              )}
            </div>
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  );
}
