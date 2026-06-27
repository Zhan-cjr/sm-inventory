import React, { useState, useEffect } from 'react';
import { useEcom, Branch } from '../context/EcomContext';
import { X, User, Phone, MapPin, ClipboardList, CheckCircle2, Printer, Loader2, ArrowRight, ArrowLeft, CreditCard, Wallet, Banknote, Map } from 'lucide-react';
import axios from 'axios';

declare global {
  interface Window {
    snap: any;
  }
}

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

  // Wizard State
  const [step, setStep] = useState<1 | 2>(1);

  // Form State
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [deliveryMethod, setDeliveryMethod] = useState<'PICKUP' | 'DELIVERY'>('PICKUP');
  const [address, setAddress] = useState('');
  const [notes, setNotes] = useState('');
  const [branchId, setBranchId] = useState('');
  const [branches, setBranches] = useState<Branch[]>([]);

  // Payment & Points
  const [usePoints, setUsePoints] = useState(false);
  const [pointsToRedeem, setPointsToRedeem] = useState<number>(0);
  const [paymentMethod, setPaymentMethod] = useState<'CASH' | 'MIDTRANS'>('MIDTRANS');
  
  const [settings, setSettings] = useState<{ point_redemption_value: number; minimum_points_to_redeem: number } | null>(null);

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Shipping
  const [destLat, setDestLat] = useState<number | null>(null);
  const [destLon, setDestLon] = useState<number | null>(null);
  const [shippingRates, setShippingRates] = useState<any[]>([]);
  const [selectedCourier, setSelectedCourier] = useState<any | null>(null);
  const [isCalculatingShipping, setIsCalculatingShipping] = useState(false);

  // Member Addresses
  const [memberAddresses, setMemberAddresses] = useState<any[]>([]);
  const [selectedAddress, setSelectedAddress] = useState<any | null>(null);

  useEffect(() => {
    if (member) {
      setName(member.name);
      setPhone(member.phone);
      if (member.address) {
        setAddress(member.address);
      }
      fetchAddresses();
    }
  }, [member, isCheckoutModalOpen]);

  const fetchAddresses = async () => {
    if (!member) return;
    try {
      const res = await axios.get('/ecommerce/customers/addresses', {
        headers: { 'X-Member-ID': member.id }
      });
      setMemberAddresses(res.data);
      if (res.data.length > 0) {
        const primary = res.data.find((a: any) => a.is_primary) || res.data[0];
        handleSelectSavedAddress(primary);
      }
    } catch (err) {
      console.error(err);
    }
  };

  const handleSelectSavedAddress = (addr: any) => {
    setSelectedAddress(addr);
    setName(addr.recipient_name);
    setPhone(addr.recipient_phone);
    setAddress(addr.full_address);
    if (addr.biteship_area_id) {
      calculateShippingRatesByArea(addr.biteship_area_id, addr.latitude, addr.longitude);
    } else if (addr.latitude && addr.longitude) {
      calculateShippingRates(addr.latitude, addr.longitude);
    }
  };

  const calculateShippingRatesByArea = async (areaId: string, lat?: number, lon?: number) => {
    if (!branchId || cart.length === 0) return;
    setIsCalculatingShipping(true);
    try {
      const payload: any = {
        branch_id: branchId,
        destination_area_id: areaId,
        items: cart.map(i => ({ product_id: i.product.id, quantity: i.quantity }))
      };
      if (lat && lon) {
        payload.destination_latitude = parseFloat(lat.toString());
        payload.destination_longitude = parseFloat(lon.toString());
      }
      const res = await axios.post('/ecommerce/shipping-rates', payload);
      setShippingRates(res.data.rates || []);
      if (res.data.rates && res.data.rates.length > 0) {
        setSelectedCourier(res.data.rates[0]);
      }
    } catch (err: any) {
      console.error(err);
      alert(err.response?.data?.message || 'Gagal menghitung ongkos kirim ke area tersebut');
    } finally {
      setIsCalculatingShipping(false);
    }
  };

  useEffect(() => {
    if (!isCheckoutModalOpen) {
      setStep(1);
      setUsePoints(false);
      setPointsToRedeem(0);
      return;
    }
    
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
  const shippingCost = selectedCourier ? selectedCourier.price : 0;
  const finalPaymentAmount = Math.max(0, totalAmount + shippingCost - discountAmount);

  const handleGetLocation = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          setDestLat(position.coords.latitude);
          setDestLon(position.coords.longitude);
          calculateShippingRates(position.coords.latitude, position.coords.longitude);
        },
        () => {
          alert('Gagal mendapatkan lokasi GPS. Pastikan izin lokasi diberikan.');
        }
      );
    } else {
      alert('Browser Anda tidak mendukung Geolocation.');
    }
  };

  const calculateShippingRates = async (lat: number, lon: number) => {
    if (!branchId || cart.length === 0) return;
    setIsCalculatingShipping(true);
    try {
      const payload = {
        branch_id: branchId,
        destination_latitude: lat,
        destination_longitude: lon,
        items: cart.map(i => ({ product_id: i.product.id, quantity: i.quantity }))
      };
      const res = await axios.post('/ecommerce/shipping-rates', payload);
      setShippingRates(res.data.rates || []);
      if (res.data.rates && res.data.rates.length > 0) {
        setSelectedCourier(res.data.rates[0]);
      }
    } catch (err: any) {
      console.error(err);
      alert(err.response?.data?.message || 'Gagal menghitung ongkos kirim');
    } finally {
      setIsCalculatingShipping(false);
    }
  };

  const handleNextStep = () => {
    if (!name || !phone) {
      setError('Nama dan Nomor Telepon wajib diisi.');
      return;
    }
    if (deliveryMethod === 'DELIVERY') {
      if (!address) {
        setError('Alamat Pengiriman wajib diisi.');
        return;
      }
      if (!selectedCourier) {
        setError('Pilihan kurir wajib dipilih jika menggunakan metode pengiriman.');
        return;
      }
    }
    setError(null);
    setStep(2);
  };

  const handleSubmitOrder = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    
    setSubmitting(true);
    setError(null);

    const payload: any = {
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
      payment_method: paymentMethod,
      shipping_cost: deliveryMethod === 'DELIVERY' && selectedCourier ? selectedCourier.price : 0,
      courier_name: deliveryMethod === 'DELIVERY' && selectedCourier ? selectedCourier.company : null,
      courier_service: deliveryMethod === 'DELIVERY' && selectedCourier ? selectedCourier.type : null,
    };

    if (deliveryMethod === 'DELIVERY') {
      if (selectedAddress) {
        payload.destination_latitude = selectedAddress.latitude;
        payload.destination_longitude = selectedAddress.longitude;
      } else {
        payload.destination_latitude = destLat;
        payload.destination_longitude = destLon;
      }
    }

    try {
      const res = await axios.post('/ecommerce/orders', payload);
      const order = res.data.order;
      
      if (res.data.member) {
        setMember(res.data.member);
      }
      clearCart();
      setIsCheckoutModalOpen(false);

      if (['MIDTRANS', 'QRIS', 'TRANSFER'].includes(order.payment_method) && order.snap_token) {
        if (window.snap) {
          window.snap.pay(order.snap_token, {
            onSuccess: function() {
              order.payment_status = 'PAID';
              setCheckoutSuccessOrder(order);
            },
            onPending: function() {
              order.payment_status = 'PENDING';
              setCheckoutSuccessOrder(order);
            },
            onError: function() {
              order.payment_status = 'FAILED';
              setCheckoutSuccessOrder(order);
            },
            onClose: function() {
              setCheckoutSuccessOrder(order);
            }
          });
        } else {
          console.error("Midtrans Snap is not loaded");
          setCheckoutSuccessOrder(order);
        }
      } else {
        setCheckoutSuccessOrder(order);
      }
    } catch (err: any) {
      console.error(err);
      setError(
        err.response?.data?.message || 
        'Gagal mengirimkan pesanan. Pastikan semua data valid.'
      );
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
    setStep(1);
    setSubmitting(false);
  };

  if (checkoutSuccessOrder) {
    const order = checkoutSuccessOrder;
    const isUnpaid = ['UNPAID', 'PENDING'].includes(order.payment_status) && order.payment_method !== 'CASH';

    return (
      <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4 print:p-0 print:bg-white">
        <div className="bg-white w-full h-full sm:h-auto sm:max-h-[90vh] max-w-lg rounded-none sm:rounded-2xl shadow-2xl overflow-hidden border border-slate-100 flex flex-col print:max-h-full print:shadow-none print:border-none print:w-full print:rounded-none">
          
          <div className="p-5 border-b border-slate-100 flex justify-between items-center print:hidden">
            <h3 className="font-bold text-slate-800 text-lg flex items-center gap-2">
              <CheckCircle2 className="text-brand-green" size={22} />
              Pesanan Dikirim!
            </h3>
            <button 
              onClick={handleCloseSuccess}
              className="p-1.5 rounded-lg hover:bg-slate-50 text-slate-400 hover:text-slate-600 transition-colors"
            >
              <X size={20} />
            </button>
          </div>

          <div className="p-6 flex-grow overflow-y-auto flex flex-col gap-6 print:overflow-visible print:p-0">
            <div className="text-center flex flex-col items-center gap-2 print:hidden">
              <div className="w-16 h-16 bg-brand-green/10 text-brand-green rounded-full flex items-center justify-center mb-1">
                <CheckCircle2 size={36} />
              </div>
              <h4 className="text-xl font-bold text-slate-800">Alhamdulillah</h4>
              <p className="text-sm text-slate-500 max-w-sm">
                Pesanan Anda telah diterima.
              </p>
              
              {isUnpaid && (
                 <div className="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl w-full text-amber-800 text-sm flex flex-col gap-2">
                   <strong>Menunggu Pembayaran</strong>
                   <span>Jika Anda belum membayar, silakan lunasi pesanan Anda untuk memproses pengiriman.</span>
                   {order.snap_token && (
                      <div className="flex flex-col gap-2 mt-1">
                        <button
                          onClick={() => {
                            if (window.snap) {
                              window.snap.pay(order.snap_token, {
                                onSuccess: function() {
                                  setCheckoutSuccessOrder({...order, payment_status: 'PAID'});
                                },
                                onPending: function() {
                                  setCheckoutSuccessOrder({...order, payment_status: 'PENDING'});
                                },
                                onError: function() {
                                  setCheckoutSuccessOrder({...order, payment_status: 'FAILED'});
                                }
                              });
                            }
                          }}
                          className="flex items-center justify-center gap-2 px-4 py-2 bg-brand-blue text-white rounded-lg font-bold hover:bg-brand-blue/90 transition-colors"
                        >
                          <CreditCard size={16} />
                          Lanjutkan Pembayaran
                        </button>
                        <button
                          onClick={async () => {
                            try {
                              const res = await axios.post(`/ecommerce/orders/${order.id}/refresh-payment`);
                              const newOrder = { ...order, snap_token: res.data.snap_token };
                              setCheckoutSuccessOrder(newOrder);
                              if (window.snap) {
                                window.snap.pay(res.data.snap_token, {
                                  onSuccess: function() { setCheckoutSuccessOrder({...newOrder, payment_status: 'PAID'}); },
                                  onPending: function() { setCheckoutSuccessOrder({...newOrder, payment_status: 'PENDING'}); },
                                  onError: function() { setCheckoutSuccessOrder({...newOrder, payment_status: 'FAILED'}); }
                                });
                              }
                            } catch (err) {
                              alert('Gagal mengganti metode pembayaran. Silakan coba lagi.');
                            }
                          }}
                          className="flex items-center justify-center gap-2 px-4 py-1.5 bg-white text-brand-blue border border-brand-blue rounded-lg font-bold hover:bg-slate-50 transition-colors text-xs"
                        >
                          Ganti Metode Pembayaran
                        </button>
                      </div>
                   )}
                 </div>
              )}
            </div>

            <div className="border border-dashed border-slate-200 rounded-xl p-5 bg-slate-50/50 print:bg-white print:border-none print:p-0">
              <div className="text-center border-b border-slate-200 pb-4 mb-4">
                <h2 className="text-2xl font-bold text-brand-blue tracking-wide">TOSERBA SELAMAT</h2>
                <p className="text-[0.65rem] text-slate-400 mt-2 font-mono">ORDER ID: {order.id}</p>
                <div className="mt-2 inline-block px-2 py-1 rounded bg-slate-200 text-[0.65rem] font-bold text-slate-700">
                  {order.payment_method === 'CASH' ? 'BAYAR DI TOKO (CASH)' : 'NON-TUNAI (ONLINE)'} 
                  {' '} - {order.payment_status}
                </div>
              </div>

              <div className="grid grid-cols-2 gap-y-2.5 text-xs pb-4 border-b border-slate-100">
                <span className="text-slate-500">Nama Pelanggan:</span>
                <span className="font-bold text-slate-800 text-right">{order.customer_name}</span>
                
                <span className="text-slate-500">Metode Pengiriman:</span>
                <span className="font-semibold text-slate-800 text-right">
                  {order.delivery_method === 'PICKUP' ? 'Ambil di Cabang' : 'Kirim ke Alamat'}
                </span>
              </div>

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

              {order.points_redeemed > 0 && (
                <div className="pt-3 flex flex-col gap-1.5 border-t border-slate-100 text-xs text-slate-600">
                  <div className="flex justify-between text-brand-green font-medium">
                    <span>Diskon Poin ({order.points_redeemed}):</span>
                    <span>-Rp {parseFloat(order.points_redeemed_discount).toLocaleString('id-ID')}</span>
                  </div>
                </div>
              )}

              <div className="pt-3 mt-1 border-t border-slate-200 flex justify-between items-center text-sm font-bold text-slate-800">
                <span>Total Tagihan</span>
                <span className="font-mono text-brand-red text-lg">
                  Rp {parseFloat(order.total_amount).toLocaleString('id-ID')}
                </span>
              </div>
            </div>
          </div>

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

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-0 sm:p-4">
      <div className="bg-white w-full h-full sm:h-auto sm:max-h-[90vh] max-w-xl rounded-none sm:rounded-2xl shadow-2xl overflow-hidden flex flex-col">
        
        <div className="p-5 border-b border-slate-100 flex justify-between items-center bg-white z-10 shadow-sm relative">
          <div>
            <h3 className="font-bold text-slate-900 text-lg flex items-center gap-2">
              <ClipboardList className="text-brand-blue" size={22} />
              Checkout Pesanan
            </h3>
            <div className="flex items-center gap-2 mt-2 text-xs font-semibold text-slate-400">
              <span className={step >= 1 ? 'text-brand-blue' : ''}>1. Pengiriman</span>
              <span className="text-slate-300">›</span>
              <span className={step >= 2 ? 'text-brand-blue' : ''}>2. Pembayaran</span>
            </div>
          </div>
          <button 
            onClick={() => setIsCheckoutModalOpen(false)}
            className="p-1.5 rounded-lg hover:bg-slate-50 text-slate-400 hover:text-slate-600 transition-colors"
          >
            <X size={20} />
          </button>
        </div>

        <div className="flex flex-col flex-grow overflow-y-auto bg-slate-50/30">
          <div className="p-6 flex flex-col gap-6 flex-grow">
            
            {error && (
              <div className="p-3 bg-red-50 text-red-600 rounded-xl text-sm font-medium border border-red-100">
                {error}
              </div>
            )}

            {step === 1 && (
              <div className="flex flex-col gap-5 animate-in fade-in slide-in-from-right-4 duration-300">
                <div className="flex flex-col gap-4 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                  <h4 className="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2 mb-1">Informasi Kontak</h4>
                  
                  <div className="flex flex-col gap-1.5">
                    <label className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                      <User size={14} /> Nama Penerima
                    </label>
                    <input
                      type="text"
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      required
                      placeholder="Masukkan nama lengkap Anda"
                      className="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/10 outline-none transition-all bg-slate-50 focus:bg-white"
                    />
                  </div>

                  <div className="flex flex-col gap-1.5">
                    <label className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                      <Phone size={14} /> No WhatsApp / Kontak
                    </label>
                    <input
                      type="tel"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      required
                      placeholder="Contoh: 081234567890"
                      className="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/10 outline-none transition-all bg-slate-50 focus:bg-white"
                    />
                  </div>
                </div>

                <div className="flex flex-col gap-4 bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                  <h4 className="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2 mb-1">Metode Pengiriman</h4>
                  
                  <div className="grid grid-cols-2 gap-3">
                    <button
                      type="button"
                      onClick={() => setDeliveryMethod('PICKUP')}
                      className={`py-4 px-4 rounded-xl border font-semibold text-sm transition-all duration-200 flex flex-col items-center gap-2 ${
                        deliveryMethod === 'PICKUP'
                          ? 'border-brand-blue bg-brand-blue/5 text-brand-blue ring-2 ring-brand-blue/10'
                          : 'border-slate-200 hover:border-slate-300 text-slate-600 hover:bg-slate-50'
                      }`}
                    >
                      <Map size={24} className={deliveryMethod === 'PICKUP' ? 'text-brand-blue' : 'text-slate-400'} />
                      <span>Ambil di Cabang</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => setDeliveryMethod('DELIVERY')}
                      className={`py-4 px-4 rounded-xl border font-semibold text-sm transition-all duration-200 flex flex-col items-center gap-2 ${
                        deliveryMethod === 'DELIVERY'
                          ? 'border-brand-blue bg-brand-blue/5 text-brand-blue ring-2 ring-brand-blue/10'
                          : 'border-slate-200 hover:border-slate-300 text-slate-600 hover:bg-slate-50'
                      }`}
                    >
                      <MapPin size={24} className={deliveryMethod === 'DELIVERY' ? 'text-brand-blue' : 'text-slate-400'} />
                      <span>Kirim ke Alamat</span>
                    </button>
                  </div>

                  <div className="flex flex-col gap-1.5 mt-2">
                    <label className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                      Cabang Toko Terdekat
                    </label>
                    <select
                      value={branchId}
                      onChange={(e) => setBranchId(e.target.value)}
                      className="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:border-brand-blue outline-none transition-all cursor-pointer"
                    >
                      <option value="">Pilih Cabang</option>
                      {branches.map((b) => (
                        <option key={b.id} value={b.id}>{b.name}</option>
                      ))}
                    </select>
                  </div>

                  {deliveryMethod === 'DELIVERY' && (
                    <div className="flex flex-col gap-1.5 mt-2 animate-in fade-in duration-300">
                      
                      {member && memberAddresses.length > 0 ? (
                        <div className="mb-3">
                          <label className="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">
                            Pilih Alamat Pengiriman
                          </label>
                          <div className="grid grid-cols-1 gap-2 max-h-40 overflow-y-auto pr-1">
                            {memberAddresses.map((addr: any) => (
                              <label 
                                key={addr.id} 
                                className={`flex items-start gap-3 p-3 border rounded-xl cursor-pointer transition-all ${selectedAddress?.id === addr.id ? 'border-brand-blue bg-brand-blue/5' : 'border-slate-200 hover:border-slate-300'}`}
                              >
                                <input 
                                  type="radio" 
                                  name="saved_address" 
                                  checked={selectedAddress?.id === addr.id}
                                  onChange={() => handleSelectSavedAddress(addr)}
                                  className="w-4 h-4 mt-0.5 text-brand-blue"
                                />
                                <div className="flex flex-col">
                                  <div className="flex items-center gap-2">
                                    <span className="font-bold text-sm text-slate-800">{addr.label}</span>
                                    {addr.is_primary && <span className="text-[9px] bg-brand-blue text-white px-1.5 rounded font-bold">UTAMA</span>}
                                  </div>
                                  <span className="text-xs text-slate-600 font-medium">{addr.recipient_name} - {addr.recipient_phone}</span>
                                  <span className="text-[11px] text-slate-500 mt-1 line-clamp-2">{addr.full_address}</span>
                                </div>
                              </label>
                            ))}
                          </div>
                          <div className="mt-2 text-right">
                            <button 
                              type="button" 
                              onClick={() => {
                                setIsCheckoutModalOpen(false);
                                document.dispatchEvent(new CustomEvent('openMemberProfile'));
                              }}
                              className="text-[10px] text-brand-blue font-bold hover:underline"
                            >
                              + Tambah Alamat di Profil
                            </button>
                          </div>
                        </div>
                      ) : (
                        <>
                          <div className="flex justify-between items-center mb-1">
                            <label className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                              Alamat Lengkap Pengiriman
                            </label>
                            <button 
                              type="button"
                              onClick={handleGetLocation}
                              className="text-[10px] bg-brand-green/10 text-brand-green font-bold px-2 py-1 rounded flex items-center gap-1 hover:bg-brand-green hover:text-white transition-colors"
                            >
                              <MapPin size={10} /> Titik GPS & Cek Ongkir
                            </button>
                          </div>
                          <textarea
                            value={address}
                            onChange={(e) => setAddress(e.target.value)}
                            required
                            rows={3}
                            placeholder="Masukkan detail alamat (jalan, nomor, RT/RW, dsb)"
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:border-brand-blue outline-none transition-all resize-none bg-slate-50 focus:bg-white"
                          />
                        </>
                      )}

                      {/* Courier Selection */}
                      {isCalculatingShipping ? (
                        <div className="flex items-center gap-2 text-xs text-slate-500 py-3 justify-center">
                          <Loader2 className="animate-spin" size={14} /> Menghitung ongkir terbaik...
                        </div>
                      ) : shippingRates.length > 0 ? (
                        <div className="mt-3">
                          <label className="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-2">
                            Pilih Layanan Kurir
                          </label>
                          <div className="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto pr-1 custom-scrollbar">
                            {shippingRates.map((rate, idx) => (
                              <label 
                                key={idx} 
                                className={`flex items-center justify-between p-3 border rounded-xl cursor-pointer transition-all ${selectedCourier?.company === rate.company && selectedCourier?.type === rate.type ? 'border-brand-blue bg-brand-blue/5' : 'border-slate-200 hover:border-slate-300'}`}
                              >
                                <div className="flex items-center gap-3">
                                  <input 
                                    type="radio" 
                                    name="courier" 
                                    checked={selectedCourier?.company === rate.company && selectedCourier?.type === rate.type}
                                    onChange={() => setSelectedCourier(rate)}
                                    className="w-4 h-4 text-brand-blue"
                                  />
                                  <div className="flex flex-col">
                                    <span className="font-bold text-sm text-slate-800 uppercase">{rate.company} - {rate.type}</span>
                                    <span className="text-xs text-slate-500">Estimasi tiba: {rate.shipment_duration_range} {rate.shipment_duration_unit}</span>
                                  </div>
                                </div>
                                <span className="font-bold text-brand-blue text-sm">
                                  Rp {rate.price.toLocaleString('id-ID')}
                                </span>
                              </label>
                            ))}
                          </div>
                        </div>
                      ) : (selectedAddress || destLat) && !isCalculatingShipping && (
                        <div className="text-xs text-red-500 mt-2 p-2 bg-red-50 rounded">
                          Kurir tidak tersedia ke alamat ini atau API Key belum diatur admin.
                        </div>
                      )}
                    </div>
                  )}

                  <div className="flex flex-col gap-1.5 mt-2">
                    <label className="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                      Catatan (Opsional)
                    </label>
                    <input
                      type="text"
                      value={notes}
                      onChange={(e) => setNotes(e.target.value)}
                      placeholder="Contoh: Titip di pos satpam"
                      className="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:border-brand-blue outline-none transition-all bg-slate-50 focus:bg-white"
                    />
                  </div>
                </div>
              </div>
            )}

            {step === 2 && (
              <div className="flex flex-col gap-5 animate-in fade-in slide-in-from-right-4 duration-300">
                
                <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                  <h4 className="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3">Ringkasan Pesanan</h4>
                  <div className="max-h-32 overflow-y-auto flex flex-col gap-2">
                    {cart.map((item) => (
                      <div key={item.product.id} className="flex justify-between items-center text-sm">
                        <span className="text-slate-600 truncate mr-2">{item.quantity}x {item.product.name}</span>
                        <span className="font-semibold text-slate-800 whitespace-nowrap">
                          Rp {(parseFloat(item.product.selling_price) * item.quantity).toLocaleString('id-ID')}
                        </span>
                      </div>
                    ))}
                    {deliveryMethod === 'DELIVERY' && selectedCourier && (
                      <div className="flex justify-between items-center text-sm pt-2 border-t border-slate-100 mt-1">
                        <span className="text-slate-600">Ongkos Kirim ({selectedCourier.company})</span>
                        <span className="font-semibold text-slate-800 whitespace-nowrap">
                          Rp {selectedCourier.price.toLocaleString('id-ID')}
                        </span>
                      </div>
                    )}
                  </div>
                </div>

                {member && settings && (
                  <div className="bg-gradient-to-r from-slate-800 to-slate-900 p-5 rounded-2xl text-white shadow-lg shadow-slate-900/10">
                    <div className="flex justify-between items-center mb-3">
                      <div className="flex items-center gap-2">
                        <Wallet size={18} className="text-brand-green" />
                        <span className="font-bold text-sm uppercase tracking-wider text-slate-200">Poin Loyalti</span>
                      </div>
                      {member.points >= settings.minimum_points_to_redeem && (
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
                          <div className="w-11 h-6 bg-slate-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-green"></div>
                        </label>
                      )}
                    </div>
                    
                    <div className="text-xs text-slate-300 mb-1">
                      Saldo Poin: <strong className="text-white text-sm">{member.points}</strong>
                    </div>

                    {!usePoints && member.points < settings.minimum_points_to_redeem && (
                       <div className="text-[0.65rem] text-slate-400 mt-2 italic">Minimal {settings.minimum_points_to_redeem} poin untuk menukar</div>
                    )}

                    {usePoints && (
                      <div className="mt-3 pt-3 border-t border-slate-700/50 flex flex-col gap-2">
                        <div className="flex justify-between items-center">
                          <span className="text-xs text-slate-300">Tukarkan:</span>
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
                              className="w-20 px-2 py-1 rounded bg-slate-700 border-none text-white text-xs font-mono text-center outline-none focus:ring-1 focus:ring-brand-green"
                            />
                            <button 
                              type="button"
                              onClick={() => setPointsToRedeem(Math.min(member.points, Math.floor(totalAmount / settings.point_redemption_value)))}
                              className="text-[0.65rem] text-brand-green hover:text-white transition-colors"
                            >
                              MAX
                            </button>
                          </div>
                        </div>
                        <div className="flex justify-between items-center text-sm font-bold text-brand-green">
                          <span>Diskon didapat:</span>
                          <span>-Rp {(pointsToRedeem * settings.point_redemption_value).toLocaleString('id-ID')}</span>
                        </div>
                      </div>
                    )}
                  </div>
                )}

                <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                  <h4 className="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3">Metode Pembayaran</h4>
                  
                  <div className="flex flex-col gap-3">
                    <label className={`flex items-center justify-between p-4 border rounded-xl cursor-pointer transition-all ${paymentMethod === 'MIDTRANS' ? 'border-brand-blue bg-brand-blue/5 ring-2 ring-brand-blue/10' : 'border-slate-200 hover:border-slate-300'}`}>
                      <div className="flex items-center gap-3">
                        <div className={`p-2 rounded-lg ${paymentMethod === 'MIDTRANS' ? 'bg-brand-blue text-white' : 'bg-slate-100 text-slate-500'}`}>
                          <CreditCard size={20} />
                        </div>
                        <div className="flex flex-col">
                          <span className="font-bold text-sm text-slate-800">Transfer Bank / QRIS</span>
                          <span className="text-xs text-slate-500">Otomatis diverifikasi oleh sistem</span>
                        </div>
                      </div>
                      <input 
                        type="radio" 
                        name="paymentMethod" 
                        value="MIDTRANS" 
                        checked={paymentMethod === 'MIDTRANS'} 
                        onChange={() => setPaymentMethod('MIDTRANS')}
                        className="w-5 h-5 text-brand-blue focus:ring-brand-blue accent-brand-blue"
                      />
                    </label>

                    <label className={`flex items-center justify-between p-4 border rounded-xl cursor-pointer transition-all ${paymentMethod === 'CASH' ? 'border-brand-green bg-brand-green/5 ring-2 ring-brand-green/10' : 'border-slate-200 hover:border-slate-300'}`}>
                      <div className="flex items-center gap-3">
                        <div className={`p-2 rounded-lg ${paymentMethod === 'CASH' ? 'bg-brand-green text-white' : 'bg-slate-100 text-slate-500'}`}>
                          <Banknote size={20} />
                        </div>
                        <div className="flex flex-col">
                          <span className="font-bold text-sm text-slate-800">Bayar Tunai (Di Toko)</span>
                          <span className="text-xs text-slate-500">Bayar langsung di kasir toko</span>
                        </div>
                      </div>
                      <input 
                        type="radio" 
                        name="paymentMethod" 
                        value="CASH" 
                        checked={paymentMethod === 'CASH'} 
                        onChange={() => setPaymentMethod('CASH')}
                        className="w-5 h-5 text-brand-green focus:ring-brand-green accent-brand-green"
                      />
                    </label>
                  </div>
                </div>

              </div>
            )}

          </div>

          <div className="p-5 border-t border-slate-100 bg-white z-10 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <div className="flex justify-between items-end mb-4 px-1">
              <span className="text-sm text-slate-500 font-medium block">Total Pembayaran</span>
              <span className="font-bold text-2xl text-slate-800 tracking-tight">
                Rp {finalPaymentAmount.toLocaleString('id-ID')}
              </span>
            </div>

            <div className="flex gap-3">
              {step === 2 && (
                <button
                  type="button"
                  onClick={() => setStep(1)}
                  className="flex items-center justify-center p-3.5 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200 transition-colors"
                >
                  <ArrowLeft size={20} />
                </button>
              )}
              
              {step === 1 ? (
                <button
                  type="button"
                  onClick={handleNextStep}
                  className="flex-grow flex items-center justify-center gap-2 bg-brand-blue text-white py-3.5 px-6 rounded-xl font-bold hover:bg-brand-blue/90 transition-colors shadow-lg shadow-brand-blue/20"
                >
                  Lanjut Pembayaran
                  <ArrowRight size={18} />
                </button>
              ) : (
                <button
                  onClick={handleSubmitOrder}
                  disabled={submitting}
                  className={`flex-grow flex items-center justify-center gap-2 text-white py-3.5 px-6 rounded-xl font-bold transition-all shadow-lg ${
                    paymentMethod === 'MIDTRANS' 
                      ? 'bg-brand-blue hover:bg-brand-blue/90 shadow-brand-blue/20' 
                      : 'bg-brand-green hover:bg-brand-green/90 shadow-brand-green/20'
                  } disabled:opacity-70 disabled:cursor-not-allowed`}
                >
                  {submitting ? (
                    <>
                      <Loader2 className="animate-spin" size={18} />
                      Memproses...
                    </>
                  ) : (
                    <>
                      {paymentMethod === 'MIDTRANS' ? 'Bayar Sekarang (QRIS/Transfer)' : 'Selesaikan Pesanan Tunai'}
                      <CheckCircle2 size={18} />
                    </>
                  )}
                </button>
              )}
            </div>
          </div>
          
        </div>
      </div>
    </div>
  );
};
