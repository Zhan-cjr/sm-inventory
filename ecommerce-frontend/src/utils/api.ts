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

axios.defaults.baseURL = API_BASE_URL;

const api = axios.create({
  baseURL: API_BASE_URL,
});

/**
 * Converts absolute localhost/127.0.0.1 image URLs (HTTP or HTTPS)
 * into relative paths, ensuring they resolve via the active host.
 */
export const getImageUrl = (url: string | null): string | null => {
  if (!url) return null;

  // If the URL contains /storage/, convert it to a relative path starting with /storage/
  if (url.includes('/storage/')) {
    return '/storage/' + url.split('/storage/')[1];
  }

  return url;
};

export default api;
