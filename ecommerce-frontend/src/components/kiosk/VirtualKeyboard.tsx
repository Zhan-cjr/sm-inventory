import React, { useState } from 'react';
import { Delete, Search, X } from 'lucide-react';

interface Props {
  onClose: () => void;
  onSearch: (keyword: string) => void;
}

const layouts = [
  ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'],
  ['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L'],
  ['Z', 'X', 'C', 'V', 'B', 'N', 'M', '-', 'SPACE']
];

export const VirtualKeyboard: React.FC<Props> = ({ onClose, onSearch }) => {
  const [input, setInput] = useState('');

  const handleKeyPress = (key: string) => {
    if (key === 'SPACE') setInput(prev => prev + ' ');
    else setInput(prev => prev + key);
  };

  const handleBackspace = () => setInput(prev => prev.slice(0, -1));

  return (
    <div className="fixed inset-0 z-[60] flex items-end justify-center bg-slate-900/80 backdrop-blur-sm p-4">
      <div className="bg-slate-800 w-full max-w-5xl rounded-t-3xl shadow-2xl overflow-hidden flex flex-col animate-in slide-in-from-bottom-12 duration-300 border-t border-slate-700">
        <div className="p-6 border-b border-slate-700 flex justify-between items-center bg-slate-900/50">
          <div className="flex-1 mr-4 relative">
            <Search className="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400" size={32} />
            <input 
              type="text" 
              value={input} 
              readOnly 
              placeholder="Ketik nama barang..."
              className="w-full bg-slate-900 text-white text-3xl font-bold rounded-2xl py-6 pl-20 pr-6 border-2 border-slate-600 outline-none"
            />
          </div>
          <button onClick={() => onSearch(input)} className="bg-brand-blue text-white font-bold text-2xl px-12 py-6 rounded-2xl shadow-lg shadow-brand-blue/30 hover:bg-blue-500 mr-4">
            CARI
          </button>
          <button onClick={onClose} className="p-6 bg-slate-700 rounded-2xl text-white hover:bg-slate-600">
            <X size={32} />
          </button>
        </div>
        <div className="p-8 bg-slate-800 flex flex-col gap-4 items-center">
          {layouts.map((row, i) => (
            <div key={i} className="flex justify-center gap-3 w-full max-w-4xl">
              {row.map(key => (
                <button
                  key={key}
                  onClick={() => handleKeyPress(key)}
                  className={`bg-slate-700 hover:bg-slate-600 active:bg-slate-500 text-white font-bold text-3xl py-6 rounded-2xl shadow-sm transition-colors ${key === 'SPACE' ? 'flex-grow' : 'w-24'}`}
                >
                  {key === 'SPACE' ? 'SPASI' : key}
                </button>
              ))}
              {i === 2 && (
                <button onClick={handleBackspace} className="bg-slate-600 hover:bg-slate-500 text-white font-bold text-3xl py-6 px-8 rounded-2xl shadow-sm flex items-center justify-center">
                  <Delete size={36} />
                </button>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
