import { useState, useEffect } from 'react';

const getApiBaseUrl = () => {
  if (process.env.NEXT_PUBLIC_API_URL) return process.env.NEXT_PUBLIC_API_URL;
  if (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
    return 'http://localhost:8080/api/company-profile';
  }
  return '/api/company-profile';
};

interface CompanyProfilePayload {
  settings: any;
  facilities: any[];
  branches: any[];
  memberTiers: any[];
}

// Module-level deduplication + 5-minute TTL cache so multiple components calling
// useCompanyProfile() on the same page share a single set of 4 API requests instead of 16+.
const CACHE_TTL_MS = 5 * 60 * 1000;
let cachedPayload: CompanyProfilePayload | null = null;
let cachedAt = 0;
let inflightPromise: Promise<CompanyProfilePayload> | null = null;

function loadCompanyProfileData(): Promise<CompanyProfilePayload> {
  if (cachedPayload && Date.now() - cachedAt < CACHE_TTL_MS) {
    return Promise.resolve(cachedPayload);
  }
  if (inflightPromise) {
    return inflightPromise;
  }

  inflightPromise = (async () => {
    const baseUrl = getApiBaseUrl();
    const [settingsRes, facilitiesRes, branchesRes, tiersRes] = await Promise.all([
      fetch(`${baseUrl}/settings`).catch(() => null),
      fetch(`${baseUrl}/facilities`).catch(() => null),
      fetch(`${baseUrl}/branches`).catch(() => null),
      fetch(`${baseUrl}/member-tiers`).catch(() => null),
    ]);

    const payload: CompanyProfilePayload = {
      settings: settingsRes?.ok ? await settingsRes.json() : null,
      facilities: facilitiesRes?.ok ? await facilitiesRes.json() : [],
      branches: branchesRes?.ok ? await branchesRes.json() : [],
      memberTiers: [],
    };

    if (tiersRes?.ok) {
      const tiersData = await tiersRes.json();
      if (Array.isArray(tiersData) && tiersData.length > 0) {
        payload.memberTiers = tiersData;
      }
    }

    cachedPayload = payload;
    cachedAt = Date.now();
    return payload;
  })().finally(() => {
    inflightPromise = null;
  });

  return inflightPromise;
}

export function useCompanyProfile() {
  const [settings, setSettings] = useState<any>(cachedPayload?.settings ?? null);
  const [facilities, setFacilities] = useState<any[]>(cachedPayload?.facilities ?? []);
  const [branches, setBranches] = useState<any[]>(cachedPayload?.branches ?? []);
  const [memberTiers, setMemberTiers] = useState<any[]>(cachedPayload?.memberTiers ?? []);
  const [isLoading, setIsLoading] = useState(!cachedPayload);

  useEffect(() => {
    let active = true;

    loadCompanyProfileData()
      .then((data) => {
        if (!active) return;
        setSettings(data.settings);
        setFacilities(data.facilities);
        setBranches(data.branches);
        if (data.memberTiers.length > 0) {
          setMemberTiers(data.memberTiers);
        }
      })
      .catch((error) => {
        console.error('Failed to fetch company profile data from backend:', error);
      })
      .finally(() => {
        if (active) setIsLoading(false);
      });

    return () => {
      active = false;
    };
  }, []);

  return { settings, facilities, branches, memberTiers, isLoading };
}
