import React from 'react';
import { User } from 'lucide-react';

export const MemberSelectModal = ({
  isOpen,
  onClose,
  customers,
  setSelectedCustomer,
  memberSearchQuery,
  setMemberSearchQuery
}) => {
  if (!isOpen) return null;

  const filteredCustomers = customers.filter(c =>
    c.name.toLowerCase().includes(memberSearchQuery.toLowerCase()) ||
    c.phone?.includes(memberSearchQuery)
  );

  return (
    <div className="change-modal-overlay">
      <div className="change-modal-content bank-select-card fade-in" style={{ maxWidth: '600px' }}>
        <User size={48} className="text-primary" />
        <h2>Pilih Member</h2>
        <div className="search-box-modern" style={{ width: '100%', marginTop: '1rem' }}>
          <input
            type="text"
            placeholder="Cari member berdasarkan nama atau nomor HP..."
            className="modern-barcode-input"
            style={{ paddingLeft: '1rem', marginBottom: '1rem' }}
            value={memberSearchQuery}
            onChange={(e) => setMemberSearchQuery(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                onClose();
              }
            }}
            autoFocus
          />
        </div>
        <div className="member-list-results" style={{ maxHeight: '300px', overflowY: 'auto', width: '100%' }}>
          {filteredCustomers.length === 0 ? (
            <div style={{ padding: '1.5rem', textAlign: 'center', color: 'var(--text-muted)' }}>
              Member tidak ditemukan
            </div>
          ) : (
            filteredCustomers.map(customer => (
              <div
                key={customer.id}
                className="held-item"
                style={{
                  cursor: 'pointer',
                  padding: '0.75rem',
                  borderBottom: '1px solid var(--border-light, #e2e8f0)',
                  borderRadius: '8px',
                  transition: 'background-color 0.15s ease'
                }}
                onMouseEnter={(e) => { e.currentTarget.style.backgroundColor = 'var(--bg-hover, rgba(0, 0, 0, 0.05))'; }}
                onMouseLeave={(e) => { e.currentTarget.style.backgroundColor = 'transparent'; }}
                onClick={() => {
                  setSelectedCustomer(customer);
                  onClose();
                  setMemberSearchQuery('');
                }}
              >
                <div style={{ textAlign: 'left', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div>
                    <div style={{ fontWeight: '700', fontSize: '1rem', color: 'var(--text-main, #0f172a)' }}>
                      {customer.name}
                    </div>
                    <div style={{ fontSize: '0.8rem', color: 'var(--text-muted, #64748b)', marginTop: '2px' }}>
                      No. HP: <span style={{ color: 'var(--text-main, #334155)', fontWeight: '500' }}>{customer.phone || '-'}</span>
                    </div>
                  </div>
                  <div style={{ textAlign: 'right' }}>
                    <div style={{ fontSize: '0.85rem', color: '#10b981', fontWeight: 'bold' }}>{customer.points || 0} Pts</div>
                    <div style={{ fontSize: '0.75rem', color: '#3b82f6', background: 'rgba(59, 130, 246, 0.1)', padding: '2px 6px', borderRadius: '4px', marginTop: '4px', display: 'inline-block', fontWeight: '600' }}>
                      {customer.member_tier || 'BRONZE'}
                    </div>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
        <div style={{ display: 'flex', gap: '1rem', marginTop: '1rem', width: '100%' }}>
          <button
            className="btn-danger"
            style={{ flex: 1 }}
            onClick={() => {
              setSelectedCustomer(null);
              onClose();
            }}
          >
            LEPAS MEMBER DARI STRUK
          </button>
          <button className="btn-secondary" style={{ flex: 1 }} onClick={onClose}>
            TUTUP (ESC)
          </button>
        </div>
      </div>
    </div>
  );
};
