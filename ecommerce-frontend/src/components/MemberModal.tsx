import React, { useState, useEffect } from 'react';
import { 
  X, CheckCircle, Award, Phone, Mail, MapPin, User, 
  LogOut, ArrowRight, 
  Loader2, Lock 
} from 'lucide-react';
import { useEcom } from '../context/EcomContext';
import axios from 'axios';
import { getImageUrl } from '../utils/api';
import AddressBookTab from './AddressBookTab';
import MapPicker from './MapPicker';
import { Search } from 'lucide-react';

const MemberModal = () => {
  const { 
    isMemberModalOpen, 
    setIsMemberModalOpen, 
    member, 
    setMember, 
    logoutMember,
    syncMemberPoints
  } = useEcom();

  // Tab State: 'login' | 'register' (when logged out) or 'profile' | 'history' | 'address' (when logged in)
  const [activeTab, setActiveTab] = useState<'login' | 'register' | 'forgot'>('login');
  const [memberTab, setMemberTab] = useState<'profile' | 'history' | 'address'>('profile');

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

  // Biteship Area Search & Map for Registration
  const [regAreaQuery, setRegAreaQuery] = useState('');
  const [regAreaResults, setRegAreaResults] = useState<any[]>([]);
  const [isSearchingRegArea, setIsSearchingRegArea] = useState(false);
  const [regSelectedArea, setRegSelectedArea] = useState<any | null>(null);
  const [regLatitude, setRegLatitude] = useState<number | null>(null);
  const [regLongitude, setRegLongitude] = useState<number | null>(null);

  // Profile Edit states
  const [isEditingProfile, setIsEditingProfile] = useState(false);
  const [editName, setEditName] = useState('');
  const [editPhone, setEditPhone] = useState('');
  const [editEmail, setEditEmail] = useState('');
  const [editAddress, setEditAddress] = useState('');

  // UI state
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  

  // Settings
  const [logoUrl, setLogoUrl] = useState<string | null>(null);
  const [orgName, setOrgName] = useState('Toserba Selamat');

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await axios.get('/ecommerce/settings');
        if (response.data.logo_url) {
          setLogoUrl(getImageUrl(response.data.logo_url));
        }
        if (response.data.name) {
          setOrgName(response.data.name);
        }
      } catch (error) {
        console.error('Error fetching settings for MemberModal:', error);
      }
    };
    if (isMemberModalOpen) {
      fetchSettings();
      syncMemberPoints();
    }
  }, [isMemberModalOpen]);

  const geocodeRegAddress = async (searchQuery: string) => {
    try {
      const res = await axios.get('https://nominatim.openstreetmap.org/search', {
        params: {
          q: searchQuery,
          format: 'json',
          limit: 1
        }
      });
      if (res.data && res.data.length > 0) {
        setRegLatitude(parseFloat(res.data[0].lat));
        setRegLongitude(parseFloat(res.data[0].lon));
      }
    } catch (err) {
      console.error('Geocoding failed:', err);
    }
  };

  const searchRegArea = async (query: string) => {
    setRegAreaQuery(query);
    if (query.length < 3) {
      setRegAreaResults([]);
      return;
    }
    
    setIsSearchingRegArea(true);
    try {
      const res = await axios.get('/ecommerce/areas/search', {
        params: { query }
      });
      setRegAreaResults(res.data.areas || []);
    } catch (err) {
      console.error(err);
    } finally {
      setIsSearchingRegArea(false);
    }
  };


  if (!isMemberModalOpen) return null;

  const handleSendOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);
    try {
      const response = await axios.post('/ecommerce/members/forgot-password', {
        phone: forgotPhone,
      });
      setSuccessMsg(response.data.message || 'OTP terkirim!');
      setTimeout(() => setSuccessMsg(null), 3000);
      setForgotStep(2);
    } catch (err: any) {
      console.error(err);
      setError(err.response?.data?.message || 'Gagal mengirim OTP.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleResetSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);
    try {
      const response = await axios.post('/ecommerce/members/reset-password', {
        phone: forgotPhone,
        otp: forgotOtp,
        password: forgotNewPassword,
      });
      setSuccessMsg(response.data.message || 'Password berhasil direset!');
      setTimeout(() => setSuccessMsg(null), 3000);
      setForgotOtp('');
      setForgotNewPassword('');
      setForgotStep(1);
      setLoginPhone(forgotPhone);
      setActiveTab('login');
    } catch (err: any) {
      console.error(err);
      setError(err.response?.data?.message || 'Gagal mereset kata sandi.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleLoginSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      const response = await axios.post('/ecommerce/members/login', {
        phone: loginPhone,
        password: loginPassword,
      });

      setMember(response.data.member);
      setSuccessMsg('Selamat datang kembali!');
      setTimeout(() => setSuccessMsg(null), 3000);
      setMemberTab('profile');
      setLoginPassword('');
    } catch (err: any) {
      console.error(err);
      setError(err.response?.data?.message || 'Nomor WhatsApp belum terdaftar.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleRegisterSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      const response = await axios.post('/ecommerce/members', {
        name: regName,
        phone: regPhone,
        email: regEmail || null,
        address: regAddress || null,
        biteship_area_id: regSelectedArea ? regSelectedArea.id : null,
        latitude: regLatitude,
        longitude: regLongitude,
        password: regPassword,
      });

      setMember(response.data.member);
      setSuccessMsg(response.data.message || 'Pendaftaran member berhasil!');
      setTimeout(() => setSuccessMsg(null), 3000);
      setMemberTab('profile');
      setRegPassword('');
    } catch (err: any) {
      console.error(err);
      setError(err.response?.data?.message || 'Gagal mendaftar. Silakan coba lagi.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleEditProfileClick = () => {
    if (member) {
      setEditName(member.name);
      setEditPhone(member.phone);
      setEditEmail(member.email || '');
      setEditAddress(member.address || '');
      setIsEditingProfile(true);
      setError(null);
    }
  };

  const handleUpdateProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!member) return;
    setIsLoading(true);
    setError(null);

    try {
      const response = await axios.put('/ecommerce/customer/profile', {
        id: member.id,
        name: editName,
        phone: editPhone,
        email: editEmail || null,
        address: editAddress || null,
      });

      setMember(response.data.user);
      setSuccessMsg(response.data.message || 'Profil berhasil diperbarui!');
      setTimeout(() => setSuccessMsg(null), 3000);
      setIsEditingProfile(false);
    } catch (err: any) {
      console.error(err);
      setError(err.response?.data?.error || err.response?.data?.message || 'Gagal memperbarui profil.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleLogout = () => {
    logoutMember();
    setActiveTab('login');
  };

  const handleClose = () => {
    setIsMemberModalOpen(false);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
      <div className="bg-white rounded-none sm:rounded-3xl shadow-2xl max-w-lg w-full h-full sm:h-[85vh] flex flex-col overflow-hidden transform transition-all animate-scale-up border border-slate-100 pb-14 sm:pb-0">
        
        {/* Header */}
        <div className="relative p-6 border-b border-slate-100 flex justify-between items-center bg-white flex-shrink-0">
          <div>
            <h3 className="text-xl font-extrabold text-slate-800 tracking-tight">
              {member ? 'Area Member' : 'Member Toserba Selamat'}
            </h3>
            <p className="text-xs text-slate-500 mt-1">
              {member 
                ? `Hi, ${member.name} - Kelola kartu member dan poin Anda.` 
                : 'Dapatkan keuntungan koin belanja & potongan harga eksklusif.'
              }
            </p>
          </div>
          <button 
            onClick={handleClose}
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

          {!member ? (
            /* ================= LOGGED OUT STATE ================= */
            <div className="space-y-6">
              {activeTab !== 'forgot' && (
                <>
                  {/* Tab Selector */}
                  <div className="flex bg-slate-100 p-1.5 rounded-2xl">
                    <button
                      onClick={() => { setActiveTab('login'); setError(null); }}
                      className={`flex-1 py-2.5 text-sm font-bold rounded-xl transition-all ${
                        activeTab === 'login' 
                          ? 'bg-white text-brand-blue shadow-sm' 
                          : 'text-slate-500 hover:text-slate-800'
                      }`}
                    >
                      Masuk Member
                    </button>
                    <button
                      onClick={() => { setActiveTab('register'); setError(null); }}
                      className={`flex-1 py-2.5 text-sm font-bold rounded-xl transition-all ${
                        activeTab === 'register' 
                          ? 'bg-white text-brand-blue shadow-sm' 
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
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
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
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
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
                            className="text-xs font-bold text-brand-blue hover:underline"
                          >
                            Lupa Kata Sandi?
                          </button>
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-3.5 bg-brand-blue text-white font-extrabold rounded-2xl hover:bg-brand-blue/95 hover:shadow-lg active:scale-[0.98] transition-all text-sm mt-4 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-brand-blue/10"
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
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
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
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
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
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
                          />
                          <Mail className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <div className="relative">
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Kecamatan / Kelurahan</label>
                        <div className="relative">
                          <input
                            type="text"
                            value={regSelectedArea ? regSelectedArea.name : regAreaQuery}
                            onChange={e => {
                              setRegSelectedArea(null);
                              searchRegArea(e.target.value);
                            }}
                            placeholder="Ketik nama kecamatan..."
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
                          />
                          <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                        
                        {isSearchingRegArea && (
                          <div className="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg p-3 text-sm text-center text-slate-500">
                            Mencari...
                          </div>
                        )}
                        {!regSelectedArea && regAreaResults.length > 0 && (
                          <div className="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                            {regAreaResults.map((area: any) => (
                              <button
                                key={area.id} type="button"
                                onClick={() => { 
                                  setRegSelectedArea(area); 
                                  setRegAreaResults([]); 
                                  geocodeRegAddress(`${area.name}, ${area.administrative_division_level_2_name}, Indonesia`);
                                }}
                                className="w-full text-left px-4 py-3 text-sm hover:bg-slate-50 border-b border-slate-100 last:border-0"
                              >
                                <span className="font-bold text-slate-700 block">{area.name}</span>
                                <span className="text-xs text-slate-400">{area.administrative_division_level_2_name}, {area.administrative_division_level_1_name}</span>
                              </button>
                            ))}
                          </div>
                        )}
                      </div>

                      <div>
                        <label className="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Detail Alamat Lengkap</label>
                        <div className="relative">
                          <textarea
                            value={regAddress}
                            onChange={(e) => setRegAddress(e.target.value)}
                            placeholder="Nama jalan, nomor rumah, RT/RW..."
                            rows={2}
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800 resize-none"
                          />
                          <MapPin className="absolute left-3.5 top-4 text-slate-400" size={16} />
                        </div>
                      </div>

                      <div className="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <MapPicker 
                          initialLat={regLatitude} 
                          initialLng={regLongitude} 
                          onLocationSelect={(lat, lng) => {
                            setRegLatitude(lat);
                            setRegLongitude(lng);
                          }}
                        />
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
                            className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
                          />
                          <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-3 bg-brand-blue text-white font-extrabold rounded-xl hover:bg-brand-blue/90 hover:shadow-lg active:scale-95 transition-all text-sm mt-4 disabled:opacity-50 flex items-center justify-center gap-2"
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
                      className="text-xs font-bold text-brand-blue hover:underline flex items-center gap-1"
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
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
                          />
                          <Phone className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-3.5 bg-brand-blue text-white font-extrabold rounded-2xl hover:bg-brand-blue/95 hover:shadow-lg active:scale-[0.98] transition-all text-sm mt-4 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-brand-blue/10"
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
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800 text-center tracking-widest font-bold"
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
                            className="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
                          />
                          <Lock className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                        </div>
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-3.5 bg-brand-blue text-white font-extrabold rounded-2xl hover:bg-brand-blue/95 hover:shadow-lg active:scale-[0.98] transition-all text-sm mt-4 disabled:opacity-50 flex items-center justify-center gap-2 shadow-md shadow-brand-blue/10"
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
          ) : (
            /* ================= LOGGED IN STATE ================= */
            <div className="space-y-6">
              {/* Virtual Member Card */}
              <div className="w-full bg-slate-950 text-white rounded-2xl p-5 shadow-xl relative overflow-hidden aspect-[1.586/1] border border-white/10 flex flex-col justify-between">
                {/* Radial glows matching logo colors */}
                <div className="absolute top-0 right-0 w-40 h-40 bg-[#E31E24]/20 rounded-full blur-[50px] pointer-events-none animate-pulse" style={{ animationDuration: '4s' }} />
                <div className="absolute bottom-0 left-0 w-40 h-40 bg-[#9DCD38]/20 rounded-full blur-[50px] pointer-events-none animate-pulse" style={{ animationDuration: '6s' }} />
                <div className="absolute top-1/2 left-1/4 w-28 h-28 bg-[#001C84]/35 rounded-full blur-[45px] pointer-events-none" />

                {/* Subtle grid lines pattern */}
                <div className="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.03)_1px,transparent_1px)] bg-[size:15px_15px] opacity-40 pointer-events-none" />

                {/* Card Top: Brand & Tier */}
                <div className="flex justify-between items-center relative z-10">
                  {logoUrl ? (
                    <div className="bg-white/95 backdrop-blur-sm px-2.5 py-1 rounded-lg flex items-center justify-center shadow-sm">
                      <img src={logoUrl} alt={orgName} className="h-5 w-auto object-contain max-w-[100px]" />
                    </div>
                  ) : (
                    <div className="flex flex-col">
                      <span className="text-sm font-extrabold tracking-tight text-white leading-none">
                        toserba <span className="text-red-500">Selamat</span>
                      </span>
                      <span className="text-[0.4rem] tracking-widest text-slate-400 uppercase mt-0.5">The Moslem Family</span>
                    </div>
                  )}
                  
                  <div className={`px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider shadow-sm flex items-center gap-1 border border-white/10 ${
                    (member.member_tier || 'BRONZE') === 'BRONZE'
                      ? 'bg-gradient-to-r from-amber-700 via-amber-800 to-amber-900 text-amber-100'
                      : (member.member_tier || 'BRONZE') === 'SILVER'
                      ? 'bg-gradient-to-r from-slate-400 via-slate-500 to-slate-600 text-slate-100'
                      : (member.member_tier || 'BRONZE') === 'GOLD'
                      ? 'bg-gradient-to-r from-yellow-400 via-amber-500 to-yellow-600 text-yellow-950'
                      : 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white'
                  }`}>
                    <Award size={9} className="animate-pulse" />
                    {member.member_tier || 'BRONZE'}
                  </div>
                </div>

                {/* Smart Card Chip */}
                <div className="flex justify-between items-start relative z-10">
                  <svg className="w-8 h-6 text-amber-500 opacity-90" viewBox="0 0 50 38" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="2" width="46" height="34" rx="6" fill="url(#chip-grad)" stroke="#d97706" strokeWidth="1.5"/>
                    <path d="M14 2v12m0 12v12M36 2v12m0 12v12M2 19h12m22 0h12" stroke="#d97706" strokeWidth="1.5"/>
                    <rect x="20" y="10" width="10" height="18" rx="3" fill="#d97706" opacity="0.3"/>
                    <defs>
                      <linearGradient id="chip-grad" x1="0" y1="0" x2="50" y2="38" gradientUnits="userSpaceOnUse">
                        <stop stopColor="#fef08a"/>
                        <stop offset="1" stopColor="#eab308"/>
                      </linearGradient>
                    </defs>
                  </svg>
                  
                  <div className="w-6 h-6 rounded-full bg-gradient-to-tr from-white/10 to-white/30 border border-white/15 flex items-center justify-center backdrop-blur-sm">
                    <div className="w-1.5 h-1.5 rounded-full bg-[#9DCD38]" />
                  </div>
                </div>

                {/* Holder Name & Information */}
                <div className="relative z-10">
                  <div className="flex flex-col">
                    <span className="text-[8px] text-slate-400 uppercase tracking-widest leading-none font-semibold">NAMA LENGKAP</span>
                    <span className="text-sm font-extrabold tracking-wide mt-1 text-white uppercase truncate max-w-[260px]">
                      {member.name}
                    </span>
                  </div>
                  
                  <div className="flex justify-between items-end mt-1.5">
                    <div className="flex flex-col">
                      <span className="text-[8px] text-slate-400 uppercase tracking-widest leading-none font-semibold">ID MEMBER</span>
                      <span className="font-mono text-[10px] tracking-wider text-slate-200 mt-1">
                        {(() => {
                          const cleanId = member.id.substring(0, 8).toUpperCase();
                          return `${cleanId.substring(0, 4)} ${cleanId.substring(4, 8)}`;
                        })()}
                      </span>
                    </div>
                    <div className="text-right flex flex-col items-end">
                      <span className="text-[8px] text-slate-400 uppercase tracking-widest leading-none font-semibold">POIN BELANJA</span>
                      <span className="text-xs font-black text-[#9DCD38] mt-0.5 flex items-center gap-1 drop-shadow-[0_1px_1px_rgba(0,0,0,0.8)]">
                        {member.points || 0} <span className="text-[8px] tracking-normal font-bold">PTS</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Sub-Tab Selector */}
              <div className="flex bg-slate-100 p-1.5 rounded-2xl flex-shrink-0">
                <button
                  onClick={() => setMemberTab('profile')}
                  className={`flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 ${
                    memberTab === 'profile' 
                      ? 'bg-white text-brand-blue shadow-sm' 
                      : 'text-slate-500 hover:text-slate-800'
                  }`}
                >
                  <User size={14} />
                  Profil
                </button>
                <button
                  onClick={() => setMemberTab('address')}
                  className={`flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5 ${
                    memberTab === 'address' 
                      ? 'bg-white text-brand-blue shadow-sm' 
                      : 'text-slate-500 hover:text-slate-800'
                  }`}
                >
                  <MapPin size={14} />
                  Alamat
                </button>
              </div>

              {memberTab === 'profile' ? (
                /* Profile & Points Tab */
                <div className="space-y-4">
                  <div className="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                      <span className="text-xs font-bold text-slate-500 uppercase tracking-wider block">Koin Selamat</span>
                      <span className="text-2xl font-black text-slate-800 mt-1 block">
                        {member.points || 0} <span className="text-xs font-bold text-slate-400">Poin Aktif</span>
                      </span>
                      <p className="text-[10px] text-slate-500 mt-1.5">Setiap Rp 1.000 belanja otomatis menghasilkan 1 poin.</p>
                    </div>
                    <div className="w-14 h-14 bg-gradient-to-tr from-emerald-400 to-emerald-600 text-white rounded-2xl flex items-center justify-center shadow-md">
                      <Award size={28} />
                    </div>
                  </div>

                  {isEditingProfile ? (
                    <form onSubmit={handleUpdateProfile} className="border border-slate-100 rounded-2xl p-4 space-y-4 bg-slate-50/50">
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Edit Profil</span>
                        <button type="button" onClick={() => setIsEditingProfile(false)} className="text-xs text-slate-500 hover:text-slate-700 font-bold">Batal</button>
                      </div>

                      <div>
                        <label className="block text-[10px] font-bold text-slate-500 mb-1">NAMA LENGKAP *</label>
                        <input
                          type="text"
                          required
                          value={editName}
                          onChange={(e) => setEditName(e.target.value)}
                          className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
                        />
                      </div>

                      <div>
                        <label className="block text-[10px] font-bold text-slate-500 mb-1">NO. WHATSAPP *</label>
                        <input
                          type="tel"
                          required
                          value={editPhone}
                          onChange={(e) => setEditPhone(e.target.value)}
                          className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
                        />
                      </div>

                      <div>
                        <label className="block text-[10px] font-bold text-slate-500 mb-1">EMAIL</label>
                        <input
                          type="email"
                          value={editEmail}
                          onChange={(e) => setEditEmail(e.target.value)}
                          className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800"
                        />
                      </div>

                      <div>
                        <label className="block text-[10px] font-bold text-slate-500 mb-1">ALAMAT LENGKAP</label>
                        <textarea
                          value={editAddress}
                          onChange={(e) => setEditAddress(e.target.value)}
                          rows={2}
                          className="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-blue/20 focus:border-brand-blue transition-all text-sm text-slate-800 resize-none"
                        />
                      </div>

                      <button
                        type="submit"
                        disabled={isLoading}
                        className="w-full py-2.5 bg-brand-blue text-white font-bold rounded-xl hover:bg-brand-blue/90 active:scale-95 transition-all text-sm mt-2 disabled:opacity-50 flex items-center justify-center gap-2"
                      >
                        {isLoading ? <Loader2 className="animate-spin" size={16} /> : 'Simpan Perubahan'}
                      </button>
                    </form>
                  ) : (
                    <div className="border border-slate-100 rounded-2xl p-4 space-y-3 bg-slate-50/50 relative">
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Informasi Akun</span>
                        <button 
                          onClick={handleEditProfileClick}
                          className="text-xs font-bold text-brand-blue hover:underline"
                        >
                          Edit Profil
                        </button>
                      </div>
                      
                      <div className="flex items-center gap-3 py-1 border-b border-slate-100 text-xs">
                        <Phone size={14} className="text-slate-400 flex-shrink-0" />
                        <div className="flex-grow">
                          <span className="text-[10px] text-slate-400 block font-medium">NO. WHATSAPP</span>
                          <span className="font-bold text-slate-800 mt-0.5 block">{member.phone}</span>
                        </div>
                      </div>

                      <div className="flex items-center gap-3 py-1 border-b border-slate-100 text-xs">
                        <Mail size={14} className="text-slate-400 flex-shrink-0" />
                        <div className="flex-grow">
                          <span className="text-[10px] text-slate-400 block font-medium">EMAIL</span>
                          <span className="font-bold text-slate-800 mt-0.5 block">{member.email || '-'}</span>
                        </div>
                      </div>

                      <div className="flex items-center gap-3 py-1 text-xs">
                        <MapPin size={14} className="text-slate-400 flex-shrink-0" />
                        <div className="flex-grow">
                          <span className="text-[10px] text-slate-400 block font-medium">ALAMAT</span>
                          <span className="font-semibold text-slate-700 mt-0.5 block leading-relaxed">
                            {member.address || 'Belum diatur'}
                          </span>
                        </div>
                      </div>
                    </div>
                  )}

                  <button
                    onClick={handleLogout}
                    className="w-full py-3.5 bg-red-50 text-red-600 font-bold rounded-2xl hover:bg-red-100 active:scale-95 transition-all text-xs flex items-center justify-center gap-2 border border-red-100"
                  >
                    <LogOut size={14} />
                    Keluar Akun Member
                  </button>
                </div>
              ) : (
                <AddressBookTab />
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MemberModal;
