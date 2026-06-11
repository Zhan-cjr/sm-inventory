import { useState, useEffect } from 'react';

const API_BASE_URL = 'http://api-sminventory/api/company-profile';

export function useCompanyProfile() {
  const [settings, setSettings] = useState<any>(null);
  const [facilities, setFacilities] = useState<any[]>([]);
  const [branches, setBranches] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function fetchData() {
      try {
        const [settingsRes, facilitiesRes, branchesRes] = await Promise.all([
          fetch(`${API_BASE_URL}/settings`),
          fetch(`${API_BASE_URL}/facilities`),
          fetch(`${API_BASE_URL}/branches`),
        ]);

        const settingsData = await settingsRes.json();
        const facilitiesData = await facilitiesRes.json();
        const branchesData = await branchesRes.json();

        setSettings(settingsData);
        setFacilities(facilitiesData);
        setBranches(branchesData);
      } catch (error) {
        console.error('Failed to fetch company profile data from backend:', error);
      } finally {
        setIsLoading(false);
      }
    }

    fetchData();
  }, []);

  return { settings, facilities, branches, isLoading };
}
