import React, { useState, useEffect } from 'react';
import { useEcom, Branch } from '../context/EcomContext';
import { X, User, Phone, MapPin, ClipboardList, CheckCircle2, Printer, Loader2, ArrowRight } from 'lucide-react';
import axios from 'axios';

export const CheckoutModal: React.FC = () => {
  const {
    cart,
    clearCart,
    isCheckoutModalOpen,
    setIsCheckoutModalOpen,
    selectedBranch,
    checkoutSuccessOrder,
    setCheckoutSuccessOrder,
    member,
    setMember,
    syncMemberPoints,
  } = useEcom();

  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [deliveryMethod, setDeliveryMethod] = useState<'PICKUP' | 'DELIVERY'>('PICKUP');
  const [address, setAddress] = useState('');
  const [notes, setNotes] = useState('');
  const [branchId, setBranchId] = useState('');
  const [branches, setBranches] = useState<Branch[]>([]);

  const [usePoints, setUsePoints] = useState(false);
  const [pointsToRedeem, setPointsToRedeem] = useState<number>(0);
  const [settings, setSettings] = useState<{ point_redemption_value: number; minimum_points_to_redeem: number } | null>(null);

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (member) {
      setName(member.name);
      setPhone(member.phone);
      if (member.address) {
        setAddress(member.address);
      }
    }
  }, [member, isCheckoutModalOpen]);

  useEffect(() => {
    if (!isCheckoutModalOpen) {
      setUsePoints(false);
      setPointsToRedeem(0);
      return;
    }
    
    // Set initial branch
    if (selectedBranch) {
      setBranchId(selectedBranch.id);
    }

    const fetchBranches = async () => {
      try {
        const res = await axios.get('/ecommerce/branches');
        setBranches(res.data);
        if (!selectedBranch && res.data.length > 0) {
          setBranchId(res.data[0].id);
        }
      } catch (err) {
        console.error(err);
      }
    };

    const fetchSettings = async () => {
      try {
        const res = await axios.get('/ecommerce/settings');
        setSettings({
          point_redemption_value: parseFloat(res.data.point_redemption_value) || 1.0,
          minimum_points_to_redeem: parseInt(res.data.minimum_points_to_redeem) || 100,
        });
      } catch (err) {
        console.error(err);
      }
    };

    fetchBranches();
    fetchSettings();
    syncMemberPoints();
  }, [isCheckoutModalOpen, selectedBranch]);

  if (!isCheckoutModalOpen && !checkoutSuccessOrder) return null;

  const totalAmount = cart.reduce(
    (sum, item) => sum + parseFloat(item.product.selling_price) * item.quantity,
    0
  );

  const discountAmount = usePoints ? pointsToRedeem * (settings?.point_redemption_value || 0) : 0;
  const finalPaymentAmount = Math.max(0, totalAmount - discountAmount);

  const handleSubmitOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!name || !phone) {
      setError('Nama dan Nomor Telepon wajib diisi.');
      return;
    }
    if (deliveryMethod === 'DELIVERY' && !address) {
      setError('Alamat Pengiriman wajib diisi.');
      return;
    }

    setSubmitting(true);
    setError(null);

    const payload = {
      customer_name: name,
      customer_phone: phone,
      delivery_method: deliveryMethod,
      delivery_address: deliveryMethod === 'DELIVERY' ? address : null,
      branch_id: branchId || null,
      notes: notes || null,
      items: cart.map((item) => ({
        product_id: item.product.id,
        quantity: item.quantity,
      })),
      points_to_redeem: usePoints ? pointsToRedeem : 0,
    };

    try {
      const res = await axios.post('/ecommerce/orders', payload);
      setCheckoutSuccessOrder(res.data.order);
      if (res.data.member) {
        setMember(res.data.member);
      }
      clearCart();
      setIsCheckoutModalOpen(false);
    } catch (err: any) {
      console.error(err);
      setError(
        err.response?.data?.message || 
        'Gagal mengirimkan pesanan. Pastikan semua data valid.'
      );
    } finally {
      setSubmitting(false);
    }
  };

  const handlePrint = () => {
    window.print();
  };

  const handleCloseSuccess = () => {
    setCheckoutSuccessOrder(null);
    setName('');
    setPhone('');
    setAddress('');
    setNotes('');
    setUsePoints(false);
    setPointsToRedeem(0);
  };

  // 1. Render Success State / Printable Receipt
  if (checkoutSuccessOrder) {
    const order = checkoutSuccessOrder;
    return (
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 print:p-0 print:bg-white">
        <div className="bg-white w-full h-full sm:h-auto sm:max-h-[90vh] max-w-lg rounded-none sm:rounded-2xl shadow-2xl overflow-hidden border border-slate-100 flex flex-col print:max-h-full print:shadow-none print:border-none print:w-full print:rounded-none">
          
          {/* Header (No print) */}
          <div className="p-5 border-b border-slate-100 flex justify-between items-center print:hidden">
            <h3 className="font-bold text-slate-800 text-lg flex items-center gap-2">
              <CheckCircle2 className="text-brand-green" size={22} />
              Pesanan Berhasil Dikirim!
            </h3>
            <button 
              onClick={handleCloseSuccess}
              className="p-1.5 rounded-lg hover:bg-slate-50 text-slate-400 hover:text-slate-600 transition-colors"
            >
              <X size={20} />
            </button>
          </div>

          {/* Receipt Content */}
          <div className="p-6 flex-grow overflow-y-auto flex flex-col gap-6 print:overflow-visible print:p-0">
            {/* Success Banner (No print) */}
            <div className="text-center flex flex-col items-center gap-2 print:hidden">
              <div className="w-16 h-16 bg-brand-green/10 text-brand-green rounded-full flex items-center justify-center mb-1">
                <CheckCircle2 size={36} />
              </div>
              <h4 className="text-xl font-bold text-slate-800">Alhamdulillah</h4>
              <p className="text-sm text-slate-500 max-w-sm">
                Pesanan Anda telah diterima dan akan segera diproses oleh petugas cabang kami.
              </p>
            </div>

            {/* Receipt Frame */}
            <div className="border border-dashed border-slate-200 rounded-xl p-5 bg-slate-50/50 print:bg-white print:border-none print:p-0">
              <div className="text-center border-b border-slate-200 pb-4 mb-4">
                <h2 className="text-2xl font-bold text-brand-blue tracking-wide">TOSERBA SELAMAT</h2>
                <p className="text-xs text-slate-500 font-medium tracking-wider mt-0.5">THE MOSLEM FAMILY</p>
                <p className="text-[0.65rem] text-slate-400 mt-2 font-mono">ORDER ID: {order.id}</p>
              </div>

              {/* Order Info */}
              <div className="grid grid-cols-2 gap-y-2.5 text-xs pb-4 border-b border-slate-100">
                <span className="text-slate-500">Nama Pelanggan:</span>
                <span className="font-bold text-slate-800 text-right">{order.customer_name}</span>
                
                <span className="text-slate-500">No Telepon:</span>
                <span className="font-mono text-slate-800 text-right">{order.customer_phone}</span>

                <span className="text-slate-500">Metode Pengiriman:</span>
                <span className="font-semibold text-slate-800 text-right">
                  {order.delivery_method === 'PICKUP' ? 'Ambil di Cabang' : 'Kirim ke Alamat'}
                </span>

                {order.branch && (
                  <>
                    <span className="text-slate-500">Cabang:</span>
                    <span className="font-semibold text-slate-800 text-right">{order.branch.name}</span>
                  </>
                )}

                {order.delivery_address && (
                  <>
                    <span className="text-slate-500">Alamat Pengiriman:</span>
                    <span className="font-semibold text-slate-800 text-right col-span-2 mt-1 bg-white p-2.5 rounded border border-slate-100">
                      {order.delivery_address}
                    </span>
                  </>
                )}
              </div>

              {/* Items Table */}
              <div className="py-4 border-b border-slate-100 flex flex-col gap-3">
                <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Rincian Belanja</span>
                {order.items?.map((item: any) => (
                  <div key={item.id} className="flex justify-between items-start text-xs">
                    <div className="max-w-[70%]">
                      <span className="font-semibold text-slate-800">{item.product?.name}</span>
                      <div className="text-[0.65rem] text-slate-500 mt-0.5">
                        {item.quantity} x Rp {parseFloat(item.price).toLocaleString('id-ID')}
                      </div>
                    </div>
                    <span className="font-mono text-slate-800">
                      Rp {parseFloat(item.subtotal).toLocaleString('id-ID')}
                    </span>
                  </div>
                ))}
              </div>

              {/* Points Redeemed Details */}
              {order.points_redeemed > 0 && (
                <div className="pt-3 flex flex-col gap-1.5 border-t border-slate-100 text-xs text-slate-600">
                  <div className="flex justify-between">
                    <span>Poin Ditukarkan:</span>
                    <span className="font-semibold">{order.points_redeemed} Poin</span>
                  </div>
                  <div className="flex justify-between text-brand-green font-medium">
                    <span>Diskon Poin:</span>
                    <span>-Rp {parseFloat(order.points_redeemed_discount).toLocaleString('id-ID')}</span>
                  </div>
                </div>
              )}

              {/* Total */}
              <div className="pt-3 mt-1 border-t border-slate-200 flex justify-between items-center text-sm font-bold text-slate-800">
                <span>Total Bayar</span>
                <span className="font-mono text-brand-red text-lg">
                  Rp {parseFloat(order.total_amount).toLocaleString('id-ID')}
                </span>
              </div>
            </div>
          </div>

          {/* Action Buttons (No print) */}
          <div className="p-5 border-t border-slate-100 bg-slate-50 flex gap-3 print:hidden">
            <button
              onClick={handlePrint}
              className="flex items-center justify-center gap-2 flex-grow bg-white text-slate-700 border border-slate-200 py-3 rounded-xl font-bold hover:bg-slate-100 transition-colors"
            >
              <Printer size={18} />
              Cetak Nota
            </button>
            <button
              onClick={handleCloseSuccess}
              className="flex items-center justify-center gap-2 flex-grow bg-brand-green text-white py-3 rounded-xl font-bold hover:bg-brand-green/90 transition-colors"
            >
              Selesai Belanja
            </button>
          </div>

        </div>
      </div>
    );
  }

  // 2. Render Checkout Form State
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4">
      <div className="bg-white w-full h-full sm:h-auto sm:max-h-[90vh] max-w-xl rounded-none sm:rounded-2xl shadow-2xl overflow-hidden border border-slate-100 flex flex-col">
        
        {/* Header */}
        <div className="p-5 border-b border-slate-100 flex justify-between items-center">
          <div>
            <h3 className="font-bold text-slate-900 text-lg flex items-center gap-2">
              <ClipboardList className="text-brand-blue" size={22} />
              Formulir Checkout Pesanan
            </h3>
            <p className="text-xs text-slate-500 mt-1">Lengkapi data Anda untuk memproses pemesanan.</p>
          </div>
          <button 
            onClick={() => setIsCheckoutModalOpen(false)}
            className="p-1.5 rounded-lg hover:bg-slate-50 text-slate-400 hover:text-slate-600 transition-colors"
          >
            <X size={20} />
          </button>
        </div>

        {/* Content */}
        <form onSubmit={handleSubmitOrder} className="flex flex-col flex-grow overflow-y-auto">
          <div className="p-6 flex flex-col gap-5 flex-grow">
            
            {error && (
              <div className="p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium border border-red-100">
                {error}
              </div>
            )}

            {/* Input Name */}
            <div className="flex flex-col gap-1.5">
              <label className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                <User size={14} className="text-slate-400" />
                Nama Penerima
              </label>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                required
                placeholder="Masukkan nama lengkap Anda"
                className="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/10 outline-none transition-all"
              />
            </div>

            {/* Input Phone */}
            <div className="flex flex-col gap-1.5">
              <label className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                <Phone size={14} className="text-slate-400" />
                No WhatsApp / Kontak
              </label>
              <input
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                required
                placeholder="Contoh: 081234567890"
                className="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/10 outline-none transition-all"
              />
            </div>

            {/* Delivery Method Choice */}
            <div className="flex flex-col gap-1.5">
              <label className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                <MapPin size={14} className="text-slate-400" />
                Metode Pengiriman
              </label>
              <div className="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  onClick={() => setDeliveryMethod('PICKUP')}
                  className={`py-3.5 px-4 rounded-xl border font-semibold text-sm transition-all duration-200 flex flex-col items-center gap-1 ${
                    deliveryMethod === 'PICKUP'
                      ? 'border-brand-green bg-brand-green/5 text-brand-green ring-2 ring-brand-green/10'
                      : 'border-slate-200 hover:border-slate-300 text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  <span>Ambil di Cabang</span>
                  <span className="text-[0.65rem] font-medium opacity-80">Bebas Ongkir</span>
                </button>
                <button
                  type="button"
                  onClick={() => setDeliveryMethod('DELIVERY')}
                  className={`py-3.5 px-4 rounded-xl border font-semibold text-sm transition-all duration-200 flex flex-col items-center gap-1 ${
                    deliveryMethod === 'DELIVERY'
                      ? 'border-brand-green bg-brand-green/5 text-brand-green ring-2 ring-brand-green/10'
                      : 'border-slate-200 hover:border-slate-300 text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  <span>Kirim ke Alamat</span>
                  <span className="text-[0.65rem] font-medium opacity-80">Hubungi CS Cabang</span>
                </button>
              </div>
            </div>

            {/* Select Branch */}
            <div className="flex flex-col gap-1.5">
              <label className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                <MapPin size={14} className="text-slate-400" />
                Cabang Toko Terdekat
              </label>
              <select
                value={branchId}
                onChange={(e) => setBranchId(e.target.value)}
                className="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/10 outline-none transition-all cursor-pointer"
              >
                <option value="">Pilih Cabang</option>
                {branches.map((b) => (
                  <option key={b.id} value={b.id}>
                    {b.name} ({b.code})
                  </option>
                ))}
              </select>
            </div>

            {/* Conditionally Show Delivery Address */}
            {deliveryMethod === 'DELIVERY' && (
              <div className="flex flex-col gap-1.5">
                <label className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                  <MapPin size={14} className="text-slate-400" />
                  Alamat Lengkap Pengiriman
                </label>
                <textarea
                  value={address}
                  onChange={(e) => setAddress(e.target.value)}
                  required
                  rows={3}
                  placeholder="Masukkan jalan, nomor rumah, RT/RW, kecamatan, kota, provinsi"
                  className="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/10 outline-none transition-all resize-none"
                />
              </div>
            )}

            {/* Notes */}
            <div className="flex flex-col gap-1.5">
              <label className="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                <ClipboardList size={14} className="text-slate-400" />
                Catatan Pesanan
              </label>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={2}
                placeholder="Contoh: request rasa manis, antar jam 10 pagi, dll."
                className="w-full px-4 py-3 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/10 outline-none transition-all resize-none"
              />
            </div>

            {/* Member Points Redemption Section */}
            {member && settings && (
              <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/60 flex flex-col gap-3">
                <div className="flex justify-between items-center">
                  <div>
                    <span className="text-xs font-bold text-slate-700 uppercase tracking-wider block">Poin Loyalti Member</span>
                    <span className="text-xs text-slate-500">
                      Anda memiliki <strong className="text-brand-blue">{member.points}</strong> poin
                    </span>
                  </div>
                  {member.points >= settings.minimum_points_to_redeem ? (
                    <label className="relative inline-flex items-center cursor-pointer">
                      <input 
                        type="checkbox" 
                        checked={usePoints} 
                        onChange={(e) => {
                          setUsePoints(e.target.checked);
                          if (e.target.checked) {
                            setPointsToRedeem(Math.min(member.points, Math.floor(totalAmount / settings.point_redemption_value)));
                          } else {
                            setPointsToRedeem(0);
                          }
                        }}
                        className="sr-only peer" 
                      />
                      <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-green"></div>
                    </label>
                  ) : (
                    <span className="text-[0.65rem] text-slate-400 font-medium">Minimal {settings.minimum_points_to_redeem} poin untuk menukar</span>
                  )}
                </div>

                {usePoints && (
                  <div className="flex flex-col gap-2 border-t border-slate-200/60 pt-2.5">
                    <div className="flex justify-between items-center gap-4">
                      <span className="text-xs text-slate-600 font-medium">Jumlah poin ditukar:</span>
                      <div className="flex items-center gap-2">
                        <input
                          type="number"
                          min={settings.minimum_points_to_redeem}
                          max={member.points}
                          value={pointsToRedeem}
                          onChange={(e) => {
                            const val = parseInt(e.target.value) || 0;
                            const cappedVal = Math.min(val, member.points, Math.floor(totalAmount / settings.point_redemption_value));
                            setPointsToRedeem(cappedVal);
                          }}
                          className="w-24 px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-800 text-xs font-mono text-center focus:border-brand-blue outline-none"
                        />
                        <button 
                          type="button"
                          onClick={() => {
                            setPointsToRedeem(Math.min(member.points, Math.floor(totalAmount / settings.point_redemption_value)));
                          }}
                          className="text-[0.65rem] text-brand-blue font-bold hover:underline"
                        >
                          Gunakan Semua
                        </button>
                      </div>
                    </div>
                    {pointsToRedeem > 0 && (
                      <div className="text-[0.65rem] text-brand-green font-medium text-right">
                        Diskon: -Rp {(pointsToRedeem * settings.point_redemption_value).toLocaleString('id-ID')}
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}

            {/* Order Items Review */}
            <div className="mt-2 border-t border-slate-100 pt-4">
              <span className="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 block">Rincian Keranjang</span>
              <div className="max-h-40 overflow-y-auto flex flex-col gap-2.5 pr-2">
                {cart.map((item) => (
                  <div key={item.product.id} className="flex justify-between items-center text-xs">
                    <span className="text-slate-700 max-w-[70%] truncate font-medium">{item.product.name}</span>
                    <div className="flex items-center gap-3">
                      <span className="text-slate-400">{item.quantity}x</span>
                      <span className="font-semibold text-slate-800">
                        Rp {(parseFloat(item.product.selling_price) * item.quantity).toLocaleString('id-ID')}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>

          </div>

          {/* Form Footer */}
          <div className="p-5 border-t border-slate-100 bg-slate-50 flex flex-col gap-3">
            {discountAmount > 0 && (
              <div className="flex justify-between items-center text-xs text-brand-green font-semibold">
                <span>Potongan Poin ({pointsToRedeem} Poin)</span>
                <span>-Rp {discountAmount.toLocaleString('id-ID')}</span>
              </div>
            )}
            <div className="flex items-center justify-between gap-6">
              <div>
                <span className="text-xs text-slate-400 font-medium block">Total Pembayaran</span>
                <span className="font-mono font-bold text-xl text-brand-red">
                  Rp {finalPaymentAmount.toLocaleString('id-ID')}
                </span>
              </div>

              <button
                type="submit"
                disabled={submitting}
                className="flex items-center justify-center gap-2 bg-brand-green text-white py-3.5 px-6 rounded-xl font-bold hover:bg-brand-green/90 disabled:opacity-70 transition-colors shadow-md shadow-brand-green/10"
              >
                {submitting ? (
                  <Loader2 className="animate-spin" size={18} />
                ) : (
                  <>
                    Kirim Pesanan
                    <ArrowRight size={18} />
                  </>
                )}
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>
  );
};
