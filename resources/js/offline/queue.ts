import { nextClientSequence, putEvidence, putOperation } from './db';
import { currentScopeKey } from './scope';

function requireScope(): string {
  const scope = currentScopeKey();
  if (!scope) throw new Error('An authenticated workspace is required for offline capture.');
  return scope;
}

export async function queueIncident(input: {
  assetPublicId: string;
  baseRevision: number;
  severity: string;
  finding: string;
  evidence?: File | null;
}): Promise<string> {
  const scopeKey = requireScope();
  const operationId = crypto.randomUUID();
  const evidenceIds: string[] = [];
  if (input.evidence) {
    const id = crypto.randomUUID();
    evidenceIds.push(id);
    await putEvidence({ clientEvidenceId: id, scopeKey, file: input.evidence, fileName: input.evidence.name, mimeType: input.evidence.type });
  }
  const now = new Date().toISOString();
  await putOperation({
    clientOperationId: operationId, scopeKey,
    clientSequence: nextClientSequence(),
    type: 'incident.create',
    payload: {
      asset_public_id: input.assetPublicId,
      base_revision: input.baseRevision,
      severity: input.severity,
      finding: input.finding,
      created_offline: !navigator.onLine,
      evidence_client_ids: evidenceIds,
    },
    status: 'pending', createdAt: now, updatedAt: now,
  });
  window.dispatchEvent(new CustomEvent('sentinelops:queue-updated'));
  return operationId;
}

export async function queueAssignment(input: { assetPublicId: string; baseRevision: number; assigneeUserId: number; reason?: string }): Promise<string> {
  const scopeKey = requireScope();
  const now = new Date().toISOString();
  const id = crypto.randomUUID();
  await putOperation({
    clientOperationId: id, scopeKey,
    clientSequence: nextClientSequence(),
    type: 'asset.assign',
    payload: { asset_public_id: input.assetPublicId, base_revision: input.baseRevision, assignee_user_id: input.assigneeUserId, reason: input.reason ?? null },
    status: 'pending', createdAt: now, updatedAt: now,
  });
  window.dispatchEvent(new CustomEvent('sentinelops:queue-updated'));
  return id;
}
