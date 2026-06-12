export async function generateStaticParams() {
  try {
    // Fetch articles to generate static paths
    const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'https://admin.toserbaselamat.id/api/company-profile';
    const res = await fetch(`${apiUrl}/articles`);
    
    if (!res.ok) {
      throw new Error(`Failed to fetch articles: ${res.status}`);
    }
    
    const articles = await res.json();
    
    if (Array.isArray(articles)) {
      return articles.map((article: any) => ({
        slug: article.slug,
      }));
    }
    
    return [];
  } catch (error) {
    console.error("Error fetching articles for static params:", error);
    // Fallback to empty array if fetch fails during build
    return [];
  }
}

export default function Layout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
