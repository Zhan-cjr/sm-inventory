import React, { useState, useEffect } from 'react';

export const LiveClock = () => {
  const [currentTime, setCurrentTime] = useState(() => {
    const offset = parseInt(localStorage.getItem('pos_server_offset') || '0', 10);
    return new Date(Date.now() + offset);
  });

  useEffect(() => {
    const timer = setInterval(() => {
      const offset = parseInt(localStorage.getItem('pos_server_offset') || '0', 10);
      setCurrentTime(new Date(Date.now() + offset));
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  return (
    <div className="pos-time-section">
      <div className="time">
        {currentTime.toLocaleTimeString('id-ID', { timeZone: 'Asia/Jakarta', hour12: false })}
      </div>
      <div className="date">
        {currentTime.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' })}
      </div>
    </div>
  );
};

export default LiveClock;

