import type { FieldValues } from 'react-hook-form';

/**
 * Restores a previously saved form draft so users don't lose work
 * after back-button navigation or accidental page leaves/refreshes.
 */
export function loadDraft<T extends FieldValues>(key: string): Partial<T> {
  try {
    const saved = sessionStorage.getItem(key);

    return saved ? JSON.parse(saved) : {};
  } catch {
    return {};
  }
}
