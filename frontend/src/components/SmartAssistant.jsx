import React, { useState, useRef, useEffect } from 'react';
import { Send, Bot, User, Loader2 } from 'lucide-react';

export const SmartAssistant = ({ user, authToken }) => {
  const [messages, setMessages] = useState([
    { role: 'assistant', content: 'Halo! Saya AI Smart Assistant Anda (dikembangkan oleh Amnal). Ada yang bisa saya bantu terkait laporan penjualan, stok barang, atau rekomendasi produk hari ini?' }
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
      // Endpoint to our Python AI Microservice (proxied via Nginx in production)
      const response = await fetch('/api/v1/ai/ask', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${authToken}` 
        },
        body: JSON.stringify({ 
          question: userMessage,
          branch_id: user?.branch_id || null
        })
      });

      if (!response.ok) {
        throw new Error('Gagal menghubungi AI Service');
      }

      const data = await response.json();
      setMessages(prev => [...prev, { role: 'assistant', content: data.response }]);
      
    } catch (error) {
      setMessages(prev => [...prev, { role: 'assistant', content: `Maaf, terjadi kesalahan komunikasi dengan server AI: ${error.message}` }]);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="glass-panel" style={{ display: 'flex', flexDirection: 'column', height: '100%', minHeight: '400px', maxHeight: '500px' }}>
      <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem', marginBottom: '1rem', borderBottom: '1px solid var(--border-light)', paddingBottom: '1rem' }}>
        <div style={{ background: 'rgba(59, 130, 246, 0.1)', padding: '8px', borderRadius: '8px' }}>
          <Bot size={20} color="var(--primary)" />
        </div>
        <div>
          <h3 style={{ fontSize: '1.1rem', fontWeight: 600, color: 'white', margin: 0 }}>Smart Assistant AI</h3>
          <p style={{ fontSize: '0.8rem', color: 'var(--text-muted)', margin: 0 }}>Silahkan tanya apa saja.</p>
        </div>
      </div>

      <div style={{ flex: 1, overflowY: 'auto', paddingRight: '0.5rem', display: 'flex', flexDirection: 'column', gap: '1rem' }}>
        {messages.map((msg, idx) => (
          <div key={idx} style={{ display: 'flex', gap: '0.75rem', alignItems: 'flex-start', flexDirection: msg.role === 'user' ? 'row-reverse' : 'row' }}>
            <div style={{ width: '30px', height: '30px', borderRadius: '50%', background: msg.role === 'user' ? 'var(--primary)' : 'rgba(255,255,255,0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
              {msg.role === 'user' ? <User size={16} color="white" /> : <Bot size={16} color="white" />}
            </div>
            <div style={{ background: msg.role === 'user' ? 'var(--primary)' : 'rgba(255,255,255,0.05)', color: 'white', padding: '10px 14px', borderRadius: '12px', borderTopRightRadius: msg.role === 'user' ? '4px' : '12px', borderTopLeftRadius: msg.role === 'assistant' ? '4px' : '12px', maxWidth: '80%', fontSize: '0.9rem', lineHeight: '1.4' }}>
              {msg.content}
            </div>
          </div>
        ))}
        {isLoading && (
          <div style={{ display: 'flex', gap: '0.75rem', alignItems: 'flex-start' }}>
             <div style={{ width: '30px', height: '30px', borderRadius: '50%', background: 'rgba(255,255,255,0.1)', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
              <Bot size={16} color="white" />
            </div>
            <div style={{ background: 'rgba(255,255,255,0.05)', color: 'var(--text-muted)', padding: '10px 14px', borderRadius: '12px', borderTopLeftRadius: '4px', fontSize: '0.9rem', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
              <Loader2 size={16} className="animate-spin" /> Sedang menganalisa data...
            </div>
          </div>
        )}
        <div ref={messagesEndRef} />
      </div>

      <form onSubmit={handleSend} style={{ display: 'flex', gap: '0.5rem', marginTop: '1rem', paddingTop: '1rem', borderTop: '1px solid var(--border-light)' }}>
        <input 
          type="text" 
          value={input}
          onChange={e => setInput(e.target.value)}
          placeholder="Tanyakan sesuatu..."
          style={{ flex: 1, background: 'rgba(0,0,0,0.2)', border: '1px solid rgba(255,255,255,0.1)', color: 'white', padding: '10px 16px', borderRadius: '20px', outline: 'none' }}
          disabled={isLoading}
        />
        <button type="submit" disabled={isLoading || !input.trim()} style={{ background: 'var(--primary)', border: 'none', width: '40px', height: '40px', borderRadius: '50%', display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'white', cursor: isLoading || !input.trim() ? 'not-allowed' : 'pointer', opacity: isLoading || !input.trim() ? 0.5 : 1 }}>
          <Send size={18} />
        </button>
      </form>
    </div>
  );
};
