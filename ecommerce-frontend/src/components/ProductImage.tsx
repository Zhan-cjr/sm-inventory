import React, { useState } from 'react';
import { Package } from 'lucide-react';

interface ProductImageProps {
  src: string | null | undefined;
  alt: string;
  className?: string;
  fallbackIconSize?: number;
}

export const ProductImage: React.FC<ProductImageProps> = ({ src, alt, className = '', fallbackIconSize = 48 }) => {
  const [hasError, setHasError] = useState(false);

  const getImageUrl = (path: string | null | undefined) => {
    if (!path) return null;
    if (path.startsWith('http')) return path;
    const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
    return `${apiUrl.replace('/api/v1', '')}/storage/${path}`;
  };

  const finalSrc = getImageUrl(src);

  if (!finalSrc || hasError) {
    return (
      <div className={`flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400 ${className}`} title={alt}>
        <Package size={fallbackIconSize} strokeWidth={1.5} />
      </div>
    );
  }

  return (
    <img 
      src={finalSrc} 
      alt={alt} 
      className={className}
      onError={() => setHasError(true)}
    />
  );
};
