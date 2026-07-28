import { useState, useEffect } from 'react';

const getApiBaseUrl = () => {
  if (process.env.NEXT_PUBLIC_API_URL) return process.env.NEXT_PUBLIC_API_URL;
  if (typeof window !== 'undefined' && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
    return 'http://localhost:8080/api/company-profile';
  }
  return '/api/company-profile';
};
export function useCompanyProfile() {
  const [settings, setSettings] = useState<any>(null);
  const [facilities, setFacilities] = useState<any[]>([]);
  const [branches, setBranches] = useState<any[]>([]);
  const [memberTiers, setMemberTiers] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function fetchData() {
      try {
        const baseUrl = getApiBaseUrl();
        const [settingsRes, facilitiesRes, branchesRes, tiersRes] = await Promise.all([
          fetch(`${baseUrl}/settings`).catch(() => null),
          fetch(`${baseUrl}/facilities`).catch(() => null),
          fetch(`${baseUrl}/branches`).catch(() => null),
          fetch(`${baseUrl}/member-tiers`).catch(() => null),
        ]);

        if (settingsRes?.ok) setSettings(await settingsRes.json());
        if (facilitiesRes?.ok) setFacilities(await facilitiesRes.json());
        if (branchesRes?.ok) setBranches(await branchesRes.json());
        if (tiersRes?.ok) {
          const tiersData = await tiersRes.json();
          if (Array.isArray(tiersData) && tiersData.length > 0) {
            setMemberTiers(tiersData);
          }
        }
      } catch (error) {
        console.error('Failed to fetch company profile data from backend:', error);
      } finally {
        setIsLoading(false);
      }
    }

    fetchData();
  }, []);

  return { settings, facilities, branches, memberTiers, isLoading };
}
