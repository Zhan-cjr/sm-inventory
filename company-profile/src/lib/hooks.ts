import { useState, useEffect } from 'react';

const API_BASE_URL = '/api/company-profile';
export function useCompanyProfile() {
  const [settings, setSettings] = useState<any>(null);
  const [facilities, setFacilities] = useState<any[]>([]);
  const [branches, setBranches] = useState<any[]>([]);
  const [memberTiers, setMemberTiers] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function fetchData() {
      try {
        const [settingsRes, facilitiesRes, branchesRes, tiersRes] = await Promise.all([
          fetch(`${API_BASE_URL}/settings`).catch(() => null),
          fetch(`${API_BASE_URL}/facilities`).catch(() => null),
          fetch(`${API_BASE_URL}/branches`).catch(() => null),
          fetch(`${API_BASE_URL}/member-tiers`).catch(() => null),
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
