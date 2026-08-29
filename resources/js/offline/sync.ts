import { deleteEvidence, getEvidence, listOperationsForScope, putEvidence, putOperation, type QueuedOperation } from './db';
import { currentScopeKey } from './scope';

function csrfToken(): string {
  return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function stageEvidence(clientEvidenceId: string, scopeKey: string): Promise<string> {
  const evidence = await getEvidence(clientEvidenceId);
  if (!evidence || evidence.scopeKey !== scopeKey) throw new Error(`Local evidence ${clientEvidenceId} is missing from the current authority scope.`);
  if (evidence.stagedToken) return evidence.stagedToken;

  const form = new FormData();
  form.append('evidence', new File([evidence.file], evidence.fileName, { type: evidence.mimeType }));
  const response = await fetch('/api/evidence/stage', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
    body: form,
  });
  if (!response.ok) throw new Error(`Evidence staging failed (${response.status}).`);
  const result = await response.json();
  await putEvidence({ ...evidence, stagedToken: result.token, stagedSha256: result.sha256 });
  return result.token as string;
}

async function serverPayload(op: QueuedOperation, scopeKey: string): Promise<Record<string, unknown>> {
  const payload = { ...op.payload };
  if (op.type === 'incident.create') {
    const clientIds = (payload.evidence_client_ids as string[] | undefined) ?? [];
    const tokens: string[] = [];
    for (const id of clientIds) tokens.push(await stageEvidence(id, scopeKey));
    delete payload.evidence_client_ids;
    payload.evidence_tokens = tokens;
  }
  return payload;
}

export async function flushQueue(): Promise<void> {
  if (!navigator.onLine) return;
  const scopeKey = currentScopeKey();
  if (!scopeKey) return;
  const operations = (await listOperationsForScope(scopeKey))
    .filter(op => op.status === 'pending' || op.status === 'retryable_error')
    .sort((a, b) => a.clientSequence - b.clientSequence);

  for (const operation of operations) {
    const syncing = { ...operation, status: 'syncing' as const, updatedAt: new Date().toISOString() };
    await putOperation(syncing);
    try {
      if (operation.scopeKey !== scopeKey) continue;
      const payload = await serverPayload(operation, scopeKey);
      const response = await fetch('/api/sync/operations', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify({ operations: [{
          client_operation_id: operation.clientOperationId,
          client_sequence: operation.clientSequence,
          type: operation.type,
          payload,
        }] }),
      });
      if (response.status === 401 || response.status === 419) {
        await putOperation({ ...operation, status: 'retryable_error', updatedAt: new Date().toISOString(), result: { code: 'SESSION_REQUIRED' } });
        return;
      }
      if (!response.ok) throw new Error(`Sync failed (${response.status}).`);
      const body = await response.json();
      const result = body.results?.[0];
      const terminal = result?.status === 'accepted' || result?.status === 'rejected';
      await putOperation({ ...operation, status: terminal ? result.status : 'retryable_error', updatedAt: new Date().toISOString(), result });
      if (result?.status === 'accepted' && operation.type === 'incident.create') {
        for (const id of ((operation.payload.evidence_client_ids as string[] | undefined) ?? [])) await deleteEvidence(id);
      }
    } catch (error) {
      await putOperation({ ...operation, status: 'retryable_error', updatedAt: new Date().toISOString(), result: { code: 'NETWORK_OR_SERVER_ERROR', message: error instanceof Error ? error.message : String(error) } });
      if (!navigator.onLine) return;
    }
  }
  window.dispatchEvent(new CustomEvent('sentinelops:queue-updated'));
}

export function installAutomaticSync(): () => void {
  const onOnline = () => void flushQueue();
  window.addEventListener('online', onOnline);
  const timer = window.setInterval(() => { if (navigator.onLine) void flushQueue(); }, 30_000);
  void flushQueue();
  return () => { window.removeEventListener('online', onOnline); window.clearInterval(timer); };
}
