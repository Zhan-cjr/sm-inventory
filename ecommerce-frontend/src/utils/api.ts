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

const attachMemberToken = (config: any) => {
  const token = localStorage.getItem('ecom_member_token');
  if (token) {
    config.headers = config.headers || {};
    config.headers['X-Member-Token'] = token;
  }
  return config;
};

const handleMemberUnauthorized = (error: any) => {
  const status = error?.response?.status;
  const reqUrl = String(error?.config?.url || '');
  if (
    status === 401 &&
    !reqUrl.includes('/ecommerce/members/login') &&
    localStorage.getItem('ecom_member')
  ) {
    window.dispatchEvent(new Event('ecom-member-unauthorized'));
  }
  return Promise.reject(error);
};

axios.interceptors.request.use(attachMemberToken);
api.interceptors.request.use(attachMemberToken);
axios.interceptors.response.use((res) => res, handleMemberUnauthorized);
api.interceptors.response.use((res) => res, handleMemberUnauthorized);

// Deduplicate concurrent & short-lived GET requests to public catalog/settings endpoints
// (prevents 4-6x /ecommerce/settings and 2x /ecommerce/products calls per homepage visit)
const DEDUP_PATHS = ['/ecommerce/settings', '/ecommerce/products', '/ecommerce/branches'];
const DEDUP_TTL_MS = 60 * 1000;
const getResponseCache = new Map<string, { data: any; expiresAt: number }>();
const inflightGets = new Map<string, Promise<any>>();

const wrapDeduplicatedGet = (instance: typeof axios | typeof api) => {
  const originalGet = instance.get.bind(instance);
  instance.get = ((url: string, config?: any) => {
    const shouldDedup = DEDUP_PATHS.some((p) => url === p || url.endsWith(p));
    if (!shouldDedup) {
      return originalGet(url, config);
    }

    const key = `${url}|${JSON.stringify(config?.params ?? {})}`;
    const cached = getResponseCache.get(key);
    if (cached && cached.expiresAt > Date.now()) {
      return Promise.resolve(cached.data);
    }

    const inflight = inflightGets.get(key);
    if (inflight) {
      return inflight;
    }

    const req = originalGet(url, config)
      .then((res) => {
        getResponseCache.set(key, { data: res, expiresAt: Date.now() + DEDUP_TTL_MS });
        return res;
      })
      .finally(() => {
        inflightGets.delete(key);
      });

    inflightGets.set(key, req);
    return req;
  }) as any;
};

wrapDeduplicatedGet(axios);
wrapDeduplicatedGet(api);

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
