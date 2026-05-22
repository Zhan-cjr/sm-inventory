import axios from 'axios';

// Resolve the base API URL dynamically.
// If VITE_API_BASE_URL is set in the environment, use it.
// Otherwise, default to relative path '/api/v1' for same-domain proxying.
const getBaseUrl = () => {
  if (import.meta.env.VITE_API_BASE_URL) {
    return import.meta.env.VITE_API_BASE_URL;
  }
  return '/api/v1';
};

export const API_BASE_URL = getBaseUrl();

const api = axios.create({
  baseURL: API_BASE_URL,
});

/**
 * Converts absolute localhost/127.0.0.1 image URLs (HTTP or HTTPS)
 * into relative paths, ensuring they resolve via the active host.
 */
export const getImageUrl = (url: string | null): string | null => {
  if (!url) return null;

  // If it points to localhost or 127.0.0.1 on port 8080, make it relative
  if (url.includes('localhost:8080') || url.includes('127.0.0.1:8080')) {
    return url.replace(/https?:\/\/(localhost|127\.0\.0\.1):8080/, '');
  }

  return url;
};

export default api;
