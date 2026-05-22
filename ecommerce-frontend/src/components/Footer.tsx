import { useState, useEffect } from 'react';
import { Mail, Phone, MapPin, X, Truck, HelpCircle } from 'lucide-react';
import { useEcom } from '../context/EcomContext';
import axios from 'axios';
import { getImageUrl } from '../utils/api';

interface Settings {
  logo_url: string | null;
  name: string;
  address: string | null;
  phone: string | null;
  email: string | null;
}

const Footer = () => {
  const { setIsMemberModalOpen, setSelectedCategory } = useEcom();
  const [settings, setSettings] = useState<Settings | null>(null);
  const [infoModal, setInfoModal] = useState<{
    isOpen: boolean;
    title: string;
    content: React.ReactNode;
  } | null>(null);

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const response = await axios.get('/ecommerce/settings');
        setSettings(response.data);
      } catch (error) {
        console.error('Error fetching settings in Footer:', error);
      }
    };
    fetchSettings();
  }, []);

  const logoUrl = getImageUrl(settings?.logo_url || null);
  const name = settings?.name || 'Toserba Selamat';
  const address = settings?.address || 'Jl. Perintis Kemerdekaan No. 123, Kota Bandung, Jawa Barat';
  const phone = settings?.phone || '+62 812-3456-7890';
  const email = settings?.email || 'cs@toserbaselamat.com';

  const openInfoModal = (title: string, content: React.ReactNode) => {
    setInfoModal({ isOpen: true, title, content });
  };

  const handleCategoryClick = (e: React.MouseEvent, categoryName: string) => {
    e.preventDefault();
    setSelectedCategory(categoryName);
    document.getElementById('catalog-section')?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <footer className="bg-slate-900 text-slate-300 pt-16 pb-8 border-t-4 border-brand-red relative">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
          
          {/* Brand Col */}
          <div className="flex flex-col gap-4">
            <div className="flex items-center gap-2">
              {logoUrl ? (
                <img src={logoUrl} alt="Logo" className="h-12 w-auto rounded-xl object-contain bg-white p-1" />
              ) : (
                <>
                  <div className="w-10 h-10 rounded-xl bg-brand-red text-white flex items-center justify-center font-bold text-2xl">
                    S
                  </div>
                  <div>
                    <span className="text-xl font-bold text-white tracking-tight">toserba <span className="text-2xl">Selamat</span></span>
                    <p className="text-[0.6rem] font-semibold text-brand-green tracking-widest uppercase mt-[-4px]">The Moslem Family</p>
                  </div>
                </>
              )}
            </div>
            <p className="text-sm text-slate-400 mt-2 leading-relaxed">
              Pasar Ummat: Untung, Murah, Manfaat, dan InsyaAllah Berkah. Pilihan terbaik untuk kebutuhan keluarga Muslim.
            </p>
            <div className="flex gap-4 mt-2">
              <a href="#" className="text-slate-400 hover:text-white font-semibold transition-colors">FB</a>
              <a href="#" className="text-slate-400 hover:text-white font-semibold transition-colors">IG</a>
              <a href="#" className="text-slate-400 hover:text-white font-semibold transition-colors">TW</a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="text-white font-bold mb-6 uppercase tracking-wider text-sm">Layanan Kami</h3>
            <ul className="flex flex-col gap-3 text-sm">
              <li>
                <button 
                  onClick={() => openInfoModal('Cara Belanja', (
                    <div className="flex flex-col gap-5">
                      <p className="text-slate-500 font-medium">Ikuti langkah mudah berikut untuk berbelanja produk berkualitas di platform online kami:</p>
                      <div className="flex flex-col gap-4">
                        <div className="flex gap-3">
                          <div className="w-7 h-7 rounded-full bg-brand-blue/10 text-brand-blue flex items-center justify-center font-bold flex-shrink-0 text-xs mt-0.5">1</div>
                          <div>
                            <h4 className="font-bold text-slate-800">Pilih Cabang Terdekat</h4>
                            <p className="text-slate-500 text-xs mt-0.5">Klik tombol "Pilih Cabang" di kanan atas untuk menyelaraskan stok barang dan harga yang sesuai dengan wilayah Anda.</p>
                          </div>
                        </div>
                        <div className="flex gap-3">
                          <div className="w-7 h-7 rounded-full bg-brand-blue/10 text-brand-blue flex items-center justify-center font-bold flex-shrink-0 text-xs mt-0.5">2</div>
                          <div>
                            <h4 className="font-bold text-slate-800">Jelajahi & Pilih Produk</h4>
                            <p className="text-slate-500 text-xs mt-0.5">Gunakan bar pencarian di bagian atas atau klik filter kategori (Pills/Footer) untuk menemukan kebutuhan harian Anda.</p>
                          </div>
                        </div>
                        <div className="flex gap-3">
                          <div className="w-7 h-7 rounded-full bg-brand-blue/10 text-brand-blue flex items-center justify-center font-bold flex-shrink-0 text-xs mt-0.5">3</div>
                          <div>
                            <h4 className="font-bold text-slate-800">Tambahkan ke Keranjang</h4>
                            <p className="text-slate-500 text-xs mt-0.5">Klik ikon keranjang pada gambar produk. Jumlah pesanan dapat diatur kembali melalui menu laci keranjang belanja.</p>
                          </div>
                        </div>
                        <div className="flex gap-3">
                          <div className="w-7 h-7 rounded-full bg-brand-blue/10 text-brand-blue flex items-center justify-center font-bold flex-shrink-0 text-xs mt-0.5">4</div>
                          <div>
                            <h4 className="font-bold text-slate-800">Gunakan Akun Member (Opsional)</h4>
                            <p className="text-slate-500 text-xs mt-0.5">Daftarkan nomor HP Anda melalui modul member untuk menikmati promo loyalitas dan poin belanja yang otomatis terakumulasi.</p>
                          </div>
                        </div>
                        <div className="flex gap-3">
                          <div className="w-7 h-7 rounded-full bg-brand-blue/10 text-brand-blue flex items-center justify-center font-bold flex-shrink-0 text-xs mt-0.5">5</div>
                          <div>
                            <h4 className="font-bold text-slate-800">Kirim & Selesaikan Pesanan</h4>
                            <p className="text-slate-500 text-xs mt-0.5">Buka keranjang belanja, klik "Checkout", lalu isi informasi pengiriman. Pesanan Anda akan dikirim ke WhatsApp cabang/admin untuk pemrosesan instan.</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  ))}
                  className="hover:text-brand-green transition-colors text-left"
                >
                  Cara Belanja
                </button>
              </li>
              <li>
                <button 
                  onClick={() => openInfoModal('Pengiriman & Pengambilan', (
                    <div className="flex flex-col gap-6">
                      <div className="flex gap-3.5 items-start">
                        <div className="p-3 bg-brand-green/10 rounded-2xl text-brand-green flex-shrink-0">
                          <Truck size={24} />
                        </div>
                        <div>
                          <h4 className="font-bold text-slate-800 text-base">Metode Pengiriman Kurir</h4>
                          <p className="text-slate-500 text-xs mt-1 leading-relaxed">
                            Belanjaan akan dikirimkan langsung ke alamat Anda oleh kurir resmi cabang terdekat atau ojek online instan. 
                            Ongkos kirim disesuaikan dengan jarak alamat rumah Anda dari titik cabang fisik yang dipilih.
                          </p>
                        </div>
                      </div>
                      
                      <div className="flex gap-3.5 items-start">
                        <div className="p-3 bg-brand-blue/10 rounded-2xl text-brand-blue flex-shrink-0">
                          <HelpCircle size={24} />
                        </div>
                        <div>
                          <h4 className="font-bold text-slate-800 text-base">Ambil di Toko (Store Pick-up)</h4>
                          <p className="text-slate-500 text-xs mt-1 leading-relaxed">
                            Anda dapat memesan semua barang belanjaan terlebih dahulu secara online, kemudian mengambilnya langsung di cabang fisik pilihan tanpa harus mengantre.
                            Tunjukan saja kode transaksi e-commerce Anda kepada petugas pelayanan di cabang.
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}
                  className="hover:text-brand-green transition-colors text-left"
                >
                  Pengiriman & Pengambilan
                </button>
              </li>
              <li>
                <button 
                  onClick={() => setIsMemberModalOpen(true)}
                  className="hover:text-brand-green transition-colors text-left"
                >
                  Daftar Member
                </button>
              </li>
              <li>
                <button 
                  onClick={() => openInfoModal('Syarat & Ketentuan', (
                    <div className="flex flex-col gap-4 text-xs">
                      <p className="text-slate-500 font-medium text-sm">Dengan bertransaksi di website e-commerce Toserba Selamat, Anda menyetujui ketentuan berikut:</p>
                      <ul className="list-disc pl-5 flex flex-col gap-2.5 text-slate-600">
                        <li><strong>Ketersediaan Produk</strong>: Stok barang yang tertera dihitung secara real-time berdasarkan stok fisik cabang yang Anda pilih.</li>
                        <li><strong>Harga Layanan</strong>: Harga dapat berubah sewaktu-waktu mengikuti kebijakan promosi cabang fisik dan kantor pusat.</li>
                        <li><strong>Poin Loyalitas</strong>: Akumulasi poin belanja bagi member terdaftar akan ditambahkan setelah pesanan Anda diproses secara resmi oleh kasir di kassa cabang.</li>
                        <li><strong>Pembatalan Transaksi</strong>: Cabang berhak membatalkan pesanan secara sepihak jika kuantitas barang fisik di gudang tidak mencukupi atau terjadi kesalahan input harga produk.</li>
                      </ul>
                    </div>
                  ))}
                  className="hover:text-brand-green transition-colors text-left"
                >
                  Syarat & Ketentuan
                </button>
              </li>
              <li>
                <button 
                  onClick={() => openInfoModal('Kebijakan Privasi', (
                    <div className="flex flex-col gap-4 text-xs">
                      <p className="text-slate-500 font-medium text-sm">Kami sangat menghargai privasi dan kepercayaan para pelanggan. Kebijakan ini menjelaskan pengelolaan data Anda:</p>
                      <ul className="list-disc pl-5 flex flex-col gap-2.5 text-slate-600">
                        <li><strong>Kerahasiaan Data</strong>: Semua data pribadi Anda (nama, no. HP, alamat pengiriman) disimpan secara aman dan terenkripsi.</li>
                        <li><strong>Penggunaan Informasi</strong>: Kontak Anda murni digunakan untuk kelancaran verifikasi pesanan, pengantaran barang, dan sinkronisasi poin loyalitas member.</li>
                        <li><strong>Pihak Ketiga</strong>: Kami berkomitmen penuh tidak akan membagikan, meminjamkan, atau menjual data pribadi Anda kepada pihak luar manapun demi komersialisasi.</li>
                      </ul>
                    </div>
                  ))}
                  className="hover:text-brand-green transition-colors text-left"
                >
                  Kebijakan Privasi
                </button>
              </li>
            </ul>
          </div>

          {/* Categories */}
          <div>
            <h3 className="text-white font-bold mb-6 uppercase tracking-wider text-sm">Kategori Favorit</h3>
            <ul className="flex flex-col gap-3 text-sm">
              <li><a href="#catalog-section" onClick={(e) => handleCategoryClick(e, 'Kebutuhan Dapur')} className="hover:text-brand-green transition-colors">Kebutuhan Dapur</a></li>
              <li><a href="#catalog-section" onClick={(e) => handleCategoryClick(e, 'Makanan & Minuman')} className="hover:text-brand-green transition-colors">Makanan & Minuman</a></li>
              <li><a href="#catalog-section" onClick={(e) => handleCategoryClick(e, 'Kesehatan & Herbal')} className="hover:text-brand-green transition-colors">Kesehatan & Herbal</a></li>
              <li><a href="#catalog-section" onClick={(e) => handleCategoryClick(e, 'Perlengkapan Ibadah')} className="hover:text-brand-green transition-colors">Perlengkapan Ibadah</a></li>
              <li><a href="#catalog-section" onClick={(e) => handleCategoryClick(e, 'Sembako')} className="hover:text-brand-green transition-colors">Sembako</a></li>
            </ul>
          </div>

          {/* Contact */}
          <div>
            <h3 className="text-white font-bold mb-6 uppercase tracking-wider text-sm">Hubungi Kami</h3>
            <ul className="flex flex-col gap-4 text-sm">
              <li className="flex items-start gap-3">
                <MapPin className="text-brand-red flex-shrink-0 mt-0.5" size={18} />
                <span>{address}</span>
              </li>
              <li className="flex items-center gap-3">
                <Phone className="text-brand-red flex-shrink-0" size={18} />
                <span>{phone}</span>
              </li>
              <li className="flex items-center gap-3">
                <Mail className="text-brand-red flex-shrink-0" size={18} />
                <span>{email}</span>
              </li>
            </ul>
          </div>

        </div>

        <div className="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-slate-500">
          <p>&copy; {new Date().getFullYear()} {name}. All rights reserved.</p>
          <div className="flex gap-4">
            <span className="flex items-center gap-1">Dibuat dengan <span className="text-brand-red">♥</span> untuk Ummat</span>
          </div>
        </div>
      </div>

      {/* Interactive Info Modal */}
      {infoModal && infoModal.isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
          <div className="bg-white text-slate-800 rounded-none sm:rounded-3xl shadow-2xl max-w-lg w-full h-full sm:h-auto sm:max-h-[85vh] overflow-hidden border border-slate-100 flex flex-col transform scale-100 transition-transform duration-300 animate-in fade-in zoom-in-95">
            {/* Header */}
            <div className="p-6 border-b border-slate-100 flex justify-between items-center bg-gradient-to-r from-slate-900 to-slate-800 text-white">
              <h3 className="font-bold text-base">{infoModal.title}</h3>
              <button 
                onClick={() => setInfoModal(null)}
                className="p-1.5 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors"
              >
                <X size={18} />
              </button>
            </div>
            
            {/* Content */}
            <div className="p-6 overflow-y-auto text-sm leading-relaxed text-slate-600 flex-grow">
              {infoModal.content}
            </div>

            {/* Footer */}
            <div className="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
              <button 
                onClick={() => setInfoModal(null)}
                className="px-5 py-2 bg-brand-blue hover:bg-brand-blue/90 text-white rounded-xl font-semibold text-xs transition-colors shadow-md shadow-brand-blue/20"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </footer>
  );
};

export default Footer;
