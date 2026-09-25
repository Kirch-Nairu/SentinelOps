export function currentScopeKey(): string | null {
  const actor = document.querySelector<HTMLMetaElement>('meta[name="sentinelops-actor"]')?.content;
  const organization = document.querySelector<HTMLMetaElement>('meta[name="sentinelops-organization"]')?.content;
  if (!actor || !organization) return null;
  return `${organization}:${actor}`;
}
