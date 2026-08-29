export type QueueStatus = 'pending' | 'syncing' | 'accepted' | 'rejected' | 'retryable_error';

export type QueuedOperation = {
  clientOperationId: string;
  scopeKey: string;
  clientSequence: number;
  type: 'incident.create' | 'asset.assign';
  payload: Record<string, unknown>;
  status: QueueStatus;
  createdAt: string;
  updatedAt: string;
  result?: unknown;
};

export type OfflineEvidence = {
  clientEvidenceId: string;
  scopeKey: string;
  file: Blob;
  fileName: string;
  mimeType: string;
  stagedToken?: string;
  stagedSha256?: string;
};

const DB_NAME = 'sentinelops-offline-v1';
const DB_VERSION = 2;

function openDb(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains('operations')) db.createObjectStore('operations', { keyPath: 'clientOperationId' });
      if (!db.objectStoreNames.contains('evidence')) db.createObjectStore('evidence', { keyPath: 'clientEvidenceId' });
      if (!db.objectStoreNames.contains('snapshots')) db.createObjectStore('snapshots', { keyPath: 'key' });
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function tx<T>(storeName: string, mode: IDBTransactionMode, fn: (store: IDBObjectStore) => IDBRequest<T>): Promise<T> {
  const db = await openDb();
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(storeName, mode);
    const request = fn(transaction.objectStore(storeName));
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
    transaction.oncomplete = () => db.close();
    transaction.onerror = () => reject(transaction.error);
  });
}

export const putOperation = (op: QueuedOperation) => tx('operations', 'readwrite', store => store.put(op));
export const getOperation = (id: string) => tx<QueuedOperation | undefined>('operations', 'readonly', store => store.get(id));
export const listOperations = () => tx<QueuedOperation[]>('operations', 'readonly', store => store.getAll());
export async function listOperationsForScope(scopeKey: string | null): Promise<QueuedOperation[]> {
  if (!scopeKey) return [];
  return (await listOperations()).filter(operation => operation.scopeKey === scopeKey);
}
export const putEvidence = (item: OfflineEvidence) => tx('evidence', 'readwrite', store => store.put(item));
export const getEvidence = (id: string) => tx<OfflineEvidence | undefined>('evidence', 'readonly', store => store.get(id));
export const deleteEvidence = (id: string) => tx('evidence', 'readwrite', store => store.delete(id));
export const putSnapshot = (key: string, value: unknown) => tx('snapshots', 'readwrite', store => store.put({ key, value, updatedAt: new Date().toISOString() }));

export function nextClientSequence(): number {
  const key = 'sentinelops-client-sequence';
  const previous = Number(localStorage.getItem(key) || '0');
  const floor = Date.now() * 1000;
  const next = Math.max(previous + 1, floor);
  localStorage.setItem(key, String(next));
  return next;
}
