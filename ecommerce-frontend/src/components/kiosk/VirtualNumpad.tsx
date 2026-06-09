import React, { useState } from 'react';
import { Delete, X, Phone } from 'lucide-react';
import axios from 'axios';

interface Props {
  onClose: () => void;
}

export const VirtualNumpad: React.FC<Props> = ({ onClose }) => {
  const [phone, setPhone] = useState('');
  const [loading, setLoading] = useState(false);
  const [member, setMember] = useState<any>(null);
  const [error, setError] = useState('');

  const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '00', '0'];

  const handlePress = (key: string) => {
    if (phone.length < 15) setPhone(prev => prev + key);
  };

  const handleCheck = async () => {
    if (!phone) return;
    setLoading(true);
    setError('');
    try {
      const res = await axios.get('/ecommerce/members/profile', { params: { phone } });
      if (res.data.member) setMember(res.data.member);
      else setError('Member tidak ditemukan!');
    } catch (e) {
      setError('Nomor HP tidak terdaftar.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
      <div className="bg-slate-800 max-w-md w-full rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200 border border-slate-700">
        
        <div className="p-6 border-b border-slate-700 flex justify-between items-center bg-slate-900/50">
          <h3 className="text-2xl font-bold text-white flex items-center gap-3">
            <Phone className="text-brand-green" /> Cek Poin Member
          </h3>
          <button onClick={onClose} className="p-3 bg-slate-700 rounded-xl text-white hover:bg-slate-600"><X size={24} /></button>
        </div>

        {member ? (
          <div className="p-10 flex flex-col items-center text-center">
            <div className="w-24 h-24 bg-brand-green/20 text-brand-green rounded-full flex items-center justify-center mb-6">
              <span className="text-4xl font-black">{member.name.charAt(0)}</span>
            </div>
            <h2 className="text-3xl font-black text-white mb-2">{member.name}</h2>
            <p className="text-slate-400 text-lg mb-8">{member.phone}</p>
            <div className="bg-slate-900/50 w-full rounded-2xl p-6 border border-slate-700">
              <div className="text-slate-400 uppercase tracking-widest text-sm font-bold mb-2">Total Poin</div>
              <div className="text-6xl font-black text-brand-green">{member.points}</div>
              <div className="mt-4 text-brand-blue font-bold px-4 py-2 bg-brand-blue/10 rounded-lg inline-block">{member.member_tier} Tier</div>
            </div>
            <button onClick={() => setMember(null)} className="mt-8 text-slate-400 hover:text-white font-bold">Cek Nomor Lain</button>
          </div>
        ) : (
          <div className="p-8">
            <div className="mb-8 relative">
              <input 
                type="text" 
                value={phone} 
                readOnly 
                placeholder="08..."
                className="w-full bg-slate-900 text-white text-4xl font-bold rounded-2xl py-6 px-6 text-center border-2 border-slate-600 outline-none tracking-widest"
              />
              {error && <div className="absolute -bottom-8 left-0 right-0 text-center text-red-400 font-bold">{error}</div>}
            </div>

            <div className="grid grid-cols-3 gap-4 mb-4">
              {keys.map(k => (
                <button key={k} onClick={() => handlePress(k)} className="bg-slate-700 hover:bg-slate-600 text-white text-4xl font-bold py-6 rounded-2xl active:bg-slate-500 transition-colors">
                  {k}
                </button>
              ))}
              <button onClick={() => setPhone(prev => prev.slice(0, -1))} className="bg-slate-600 hover:bg-slate-500 text-white text-3xl font-bold py-6 rounded-2xl flex items-center justify-center">
                <Delete size={32} />
              </button>
            </div>
            
            <button 
              onClick={handleCheck} 
              disabled={loading}
              className="w-full bg-brand-green text-white font-bold text-2xl py-6 rounded-2xl hover:bg-green-500 shadow-lg shadow-brand-green/20 mt-4"
            >
              {loading ? 'MENGECEK...' : 'CEK POIN'}
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
