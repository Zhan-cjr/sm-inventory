import React, { useState, useRef, useEffect } from 'react';
import { Send, Bot, User, Loader2 } from 'lucide-react';

export const SmartAssistant = ({ user, authToken }) => {
  const [messages, setMessages] = useState([
    { role: 'assistant', content: 'Halo! Saya AI Smart Assistant Anda (dikembangkan oleh Zhan_soft). Ada yang bisa saya bantu terkait laporan penjualan, stok barang, atau rekomendasi produk hari ini?' }
  ]);
  const [input, setInput] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef(null);

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const handleSend = async (e) => {
    e.preventDefault();
    if (!input.trim()) return;

    const userMessage = input.trim();
    setMessages(prev => [...prev, { role: 'user', content: userMessage }]);
    setInput('');
    setIsLoading(true);

    try {
      const response = await fetch('/api/v1/ai-proxy/ask', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${authToken}` 
        },
        body: JSON.stringify({ 
          question: userMessage,
          branch_id: user?.branch_id || null
        })
      });

      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || data.error || 'Gagal menghubungi AI Service');
      }

      setMessages(prev => [...prev, { role: 'assistant', content: data.response || data.answer || 'AI memberikan respon kosong' }]);
      
    } catch (error) {
      setMessages(prev => [...prev, { role: 'assistant', content: `INFO: ${error.message}` }]);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="pwa-card" style={{ display: 'flex', flexDirection: 'column', height: '100%', minHeight: '420px', maxHeight: '520px' }}>
      {/* Assistant Header */}
      <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1rem', borderBottom: '1px solid var(--border-light)', paddingBottom: '0.85rem' }}>
        <div style={{ background: 'linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%)', padding: '8px', borderRadius: '12px', color: 'white', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
          <Bot size={20} />
        </div>
        <div>
          <h3 style={{ fontSize: '1.05rem', fontWeight: 800, color: 'var(--text-main)', margin: 0 }}>Smart Assistant AI</h3>
          <p style={{ fontSize: '0.78rem', color: 'var(--text-muted)', margin: 0 }}>Silakan tanya laporan atau saran produk.</p>
        </div>
      </div>

      {/* Messages Feed */}
      <div style={{ flex: 1, overflowY: 'auto', paddingRight: '0.25rem', display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
        {messages.map((msg, idx) => (
          <div key={idx} style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start', flexDirection: msg.role === 'user' ? 'row-reverse' : 'row' }}>
            <div style={{ 
              width: '32px', 
              height: '32px', 
              borderRadius: '10px', 
              background: msg.role === 'user' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%)', 
              display: 'flex', 
              alignItems: 'center', 
              justify: 'center', 
              flexShrink: 0,
              color: 'white'
            }}>
              {msg.role === 'user' ? <User size={16} /> : <Bot size={16} />}
            </div>
            <div style={{ 
              background: msg.role === 'user' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(255, 255, 255, 0.05)', 
              color: 'var(--text-main)', 
              border: '1px solid var(--border-light)',
              padding: '10px 14px', 
              borderRadius: '16px', 
              borderTopRightRadius: msg.role === 'user' ? '4px' : '16px', 
              borderTopLeftRadius: msg.role === 'assistant' ? '4px' : '16px', 
              maxWidth: '82%', 
              fontSize: '0.88rem', 
              lineHeight: '1.45',
              fontWeight: 500
            }}>
              {msg.content}
            </div>
          </div>
        ))}
        {isLoading && (
          <div style={{ display: 'flex', gap: '0.65rem', alignItems: 'flex-start' }}>
            <div style={{ width: '32px', height: '32px', borderRadius: '10px', background: 'linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, color: 'white' }}>
              <Bot size={16} />
            </div>
            <div style={{ background: 'rgba(255,255,255,0.05)', border: '1px solid var(--border-light)', color: 'var(--text-muted)', padding: '10px 14px', borderRadius: '16px', borderTopLeftRadius: '4px', fontSize: '0.85rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <Loader2 size={16} className="animate-spin" /> Menganalisis data...
            </div>
          </div>
        )}
        <div ref={messagesEndRef} />
      </div>

      {/* Input Form */}
      <form onSubmit={handleSend} style={{ display: 'flex', gap: '0.5rem', marginTop: '0.85rem', paddingTop: '0.85rem', borderTop: '1px solid var(--border-light)' }}>
        <input 
          type="text" 
          value={input}
          onChange={e => setInput(e.target.value)}
          placeholder="Tanyakan sesuatu ke AI..."
          style={{ flex: 1, background: 'rgba(0,0,0,0.1)', border: '1px solid var(--border-light)', color: 'var(--text-main)', padding: '10px 16px', borderRadius: '14px', outline: 'none', fontSize: '0.88rem', fontWeight: 500 }}
          disabled={isLoading}
        />
        <button 
          type="submit" 
          disabled={isLoading || !input.trim()} 
          style={{ background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)', border: 'none', width: '42px', height: '42px', borderRadius: '14px', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'white', cursor: isLoading || !input.trim() ? 'not-allowed' : 'pointer', opacity: isLoading || !input.trim() ? 0.5 : 1 }}
        >
          <Send size={18} />
        </button>
      </form>
    </div>
  );
};
