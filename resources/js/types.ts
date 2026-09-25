export type Role = 'administrator' | 'supervisor' | 'technician' | 'security_officer' | 'auditor';
export type SharedProps = {
  auth: {
    user: { id: number; name: string; email: string } | null;
    organization: { public_id: string; name: string; slug: string } | null;
    role: Role | null;
  };
  flash: { error?: string | null };
};

export type AssetSnapshot = {
  public_id: string;
  code: string;
  name: string;
  status: string;
  revision: number;
  location?: { id: number; code?: string; name: string } | null;
};
