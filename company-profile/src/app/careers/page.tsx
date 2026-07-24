"use client";

import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Briefcase, MapPin, Clock, Users, ArrowRight, ShieldCheck, HeartHandshake, Zap, CheckCircle2, X, Send } from "lucide-react";

interface JobItem {
  id: number;
  title: string;
  department: string;
  type: string;
  location: string;
  desc: string;
  requirements: string[];
}

const jobListings: JobItem[] = [
  {
    id: 1,
    title: "Store Manager Trainee",
    department: "Operations",
    type: "Full-Time",
    location: "Cianjur & Sukabumi",
    desc: "Memimpin operasional toko, mengelola tim penjualan, dan memastikan kualitas standar pelayanan syariah terbaik di cabang.",
    requirements: ["Pendidikan S1 semua jurusan", "Pengalaman min. 2 tahun di bidang retail", "Memiliki jiwa kepemimpinan & orientasi target"]
  },
  {
    id: 2,
    title: "Digital Marketing & Social Media",
    department: "Marketing",
    type: "Full-Time",
    location: "Head Office - Cianjur",
    desc: "Merancang kampanye promo online, membuat konten media sosial kreatif, dan mengelola pemasaran event.",
    requirements: ["Pendidikan D3/S1 Komunikasi/DKV/Marketing", "Menguasai TikTok, IG Reels, Canva & Video Editing", "Portofolio konten kreatif diutamakan"]
  },
  {
    id: 3,
    title: "Customer Service & Kasir",
    department: "Service",
    type: "Shift",
    location: "Semua Cabang Store",
    desc: "Memberikan pelayanan ramah sepenuh hati kepada pelanggan, mengelola kasir transaksi offline, dan membantu informasi promo.",
    requirements: ["Pendidikan min. SMA/SMK sederajat", "Penampilan rapi, ramah, dan jujur", "Bersedia bekerja dalam sistem shift tgl merah/akhir pekan"]
  },
  {
    id: 4,
    title: "IT Support & POS Administrator",
    department: "IT",
    type: "Full-Time",
    location: "Cianjur City Center",
    desc: "Memelihara sistem POS kasir, jaringan LAN/Wi-Fi toko, pemeliharaan hardware komputer, dan dukungan server inventory.",
    requirements: ["Pendidikan D3/S1 Teknik Informatika/Sistem Informasi", "Memahami troubleshooting hardware & jaringan TCP/IP", "Pengalaman support POS retail menjadi nilai tambah"]
  }
];

export default function CareersPage() {
  const [selectedDept, setSelectedDept] = useState("Semua");
  const [activeJob, setActiveJob] = useState<JobItem | null>(null);
  const [applyJob, setApplyJob] = useState<JobItem | null>(null);
  const [appliedSuccess, setAppliedSuccess] = useState(false);

  const departments = ["Semua", "Operations", "Marketing", "Service", "IT"];

  const filteredJobs = selectedDept === "Semua"
    ? jobListings
    : jobListings.filter(j => j.department.toLowerCase() === selectedDept.toLowerCase());

  const handleApplySubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setAppliedSuccess(true);
    setTimeout(() => {
      setAppliedSuccess(false);
      setApplyJob(null);
    }, 2500);
  };

  return (
    <div className="bg-slate-50 min-h-screen pt-28 pb-32 text-slate-900 relative overflow-hidden">
      
      {/* Background Decor */}
      <div className="absolute top-0 right-0 w-[700px] h-[700px] bg-primary/10 rounded-full blur-[140px] pointer-events-none" />
      <div className="absolute bottom-0 left-0 w-[700px] h-[700px] bg-secondary/10 rounded-full blur-[140px] pointer-events-none" />

      <div className="container mx-auto px-4 md:px-8 max-w-6xl relative z-10">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-16 space-y-4">
          <span className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold uppercase tracking-widest">
            <Users size={15} /> Karir &amp; Talenta
          </span>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-black text-slate-900 tracking-tight">
            Berkarir di <span className="bg-gradient-to-r from-primary via-primary-light to-secondary text-gradient">Toserba Selamat</span>
          </h1>
          <p className="text-slate-600 font-medium text-base sm:text-lg leading-relaxed">
            Bergabunglah bersama keluarga besar Toserba Selamat. Mari bertumbuh bersama jaringan ritel &amp; hospitality yang menjunjung tinggi profesionalisme dan nilai-nilai Islami.
          </p>
        </div>

        {/* Culture Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-20">
          <div className="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-md">
            <div className="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mb-5">
              <HeartHandshake size={24} />
            </div>
            <h3 className="text-lg font-bold text-slate-900 mb-2">Suasana Kekeluargaan</h3>
            <p className="text-xs text-slate-600 font-medium leading-relaxed">Lingkungan kerja yang hangat, ramah, dan mengedepankan saling menghargai antar karyawan.</p>
          </div>

          <div className="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-md">
            <div className="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-5">
              <ShieldCheck size={24} />
            </div>
            <h3 className="text-lg font-bold text-slate-900 mb-2">Lingkungan Syariah</h3>
            <p className="text-xs text-slate-600 font-medium leading-relaxed">Fasilitas ibadah lengkap, suasana kerja kondusif, dan prinsip kerja yang transparan &amp; adil.</p>
          </div>

          <div className="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-md">
            <div className="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center mb-5">
              <Zap size={24} />
            </div>
            <h3 className="text-lg font-bold text-slate-900 mb-2">Pengembangan Karir</h3>
            <p className="text-xs text-slate-600 font-medium leading-relaxed">Pelatihan kepemimpinan berkala dan jalur karir terbuka untuk seluruh karyawan berprestasi.</p>
          </div>
        </div>

        {/* Job Openings Section */}
        <div className="space-y-8">
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900">Lowongan Kerja Tersedia</h2>
              <p className="text-xs text-slate-500 font-medium">Temukan posisi yang sesuai dengan keahlian Anda.</p>
            </div>

            {/* Department Filters */}
            <div className="flex flex-wrap gap-2">
              {departments.map((dept) => (
                <button
                  key={dept}
                  onClick={() => setSelectedDept(dept)}
                  className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                    selectedDept === dept
                      ? "bg-primary text-white shadow-md shadow-primary/20"
                      : "bg-white text-slate-600 border border-slate-200 hover:bg-slate-100"
                  }`}
                >
                  {dept}
                </button>
              ))}
            </div>
          </div>

          {/* Job List Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {filteredJobs.map((job) => (
              <div key={job.id} className="bg-white rounded-3xl p-8 border border-slate-200/80 shadow-md hover:shadow-xl transition-all flex flex-col justify-between space-y-4">
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="px-3 py-1 bg-primary/10 text-primary font-bold text-[10px] rounded-full uppercase tracking-wider">
                      {job.department}
                    </span>
                    <span className="px-3 py-1 bg-slate-100 text-slate-600 font-bold text-[10px] rounded-full uppercase">
                      {job.type}
                    </span>
                  </div>

                  <h3 className="text-xl font-extrabold text-slate-900">{job.title}</h3>
                  <div className="flex items-center gap-2 text-xs font-semibold text-slate-500">
                    <MapPin size={14} className="text-secondary" />
                    <span>{job.location}</span>
                  </div>

                  <p className="text-xs text-slate-600 font-medium leading-relaxed">
                    {job.desc}
                  </p>
                </div>

                <div className="pt-4 border-t border-slate-100 flex items-center justify-between gap-3">
                  <button
                    onClick={() => setActiveJob(job)}
                    className="text-xs font-bold text-primary hover:underline"
                  >
                    Syarat Positions
                  </button>
                  <button
                    onClick={() => setApplyJob(job)}
                    className="px-5 py-2.5 bg-primary hover:bg-primary-light text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-primary/20 flex items-center gap-2"
                  >
                    <span>Lamar Sekarang</span>
                    <ArrowRight size={14} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>

      {/* Requirement Details Modal */}
      <AnimatePresence>
        {activeJob && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.95, opacity: 0 }} className="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl relative border border-slate-200">
              <button onClick={() => setActiveJob(null)} className="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors">
                <X size={18} />
              </button>
              <h3 className="text-2xl font-black text-slate-900 mb-1">{activeJob.title}</h3>
              <p className="text-xs text-primary font-bold uppercase tracking-wider mb-4">{activeJob.department} • {activeJob.location}</p>

              <div className="space-y-3 mb-6">
                <h4 className="font-extrabold text-xs text-slate-800 uppercase tracking-wider">Kualifikasi Syarat:</h4>
                <ul className="space-y-2 text-xs text-slate-600 font-medium">
                  {activeJob.requirements.map((req, idx) => (
                    <li key={idx} className="flex items-start gap-2">
                      <CheckCircle2 size={16} className="text-accent shrink-0 mt-0.5" />
                      <span>{req}</span>
                    </li>
                  ))}
                </ul>
              </div>

              <button onClick={() => { setApplyJob(activeJob); setActiveJob(null); }} className="w-full py-3 bg-primary text-white font-bold text-xs rounded-xl shadow-md">
                Lamar Posisi Ini
              </button>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Apply Form Modal */}
      <AnimatePresence>
        {applyJob && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <motion.div initial={{ scale: 0.95, opacity: 0 }} animate={{ scale: 1, opacity: 1 }} exit={{ scale: 0.95, opacity: 0 }} className="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl relative border border-slate-200">
              <button onClick={() => setApplyJob(null)} className="absolute top-4 right-4 w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors">
                <X size={18} />
              </button>

              <h3 className="text-xl font-black text-slate-900 mb-1">Lamar Posisi</h3>
              <p className="text-xs text-primary font-bold mb-6">{applyJob.title} - {applyJob.location}</p>

              {appliedSuccess ? (
                <div className="py-8 text-center space-y-3">
                  <div className="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto">
                    <CheckCircle2 size={32} />
                  </div>
                  <h4 className="font-extrabold text-slate-900 text-lg">Lamaran Berhasil Dikirim!</h4>
                  <p className="text-xs text-slate-500 font-medium">Tim HRD kami akan meninjau CV Anda.</p>
                </div>
              ) : (
                <form onSubmit={handleApplySubmit} className="space-y-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" required placeholder="Sesuai KTP" className="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium bg-slate-50 outline-none" />
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp *</label>
                    <input type="tel" required placeholder="0812xxxx" className="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium bg-slate-50 outline-none" />
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 mb-1">Link CV / Portofolio (Google Drive / LinkedIn) *</label>
                    <input type="url" required placeholder="https://..." className="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-medium bg-slate-50 outline-none" />
                  </div>

                  <button type="submit" className="w-full py-3 bg-secondary text-white font-bold text-xs rounded-xl shadow-md flex items-center justify-center gap-2">
                    <span>Kirim Lamaran</span>
                    <Send size={14} />
                  </button>
                </form>
              )}
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
