import { useEffect, useRef } from 'react';
import type { FieldValues, UseFormReturn } from 'react-hook-form';
import { useWatch } from 'react-hook-form';

/**
 * Persists form values to sessionStorage so drafts survive back-button
 * navigation and accidental page leaves/refreshes. Writes are debounced
 * to avoid blocking the main thread on every keystroke in large text fields.
 * Pass null as a key to disable persistence (eg: when editing, not creating).
 */
export function useFormDraft<T extends FieldValues>(key: string | null, form: UseFormReturn<T>) {
  const values = useWatch({ control: form.control });
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    if (key) {
      timerRef.current = setTimeout(() => {
        sessionStorage.setItem(key, JSON.stringify(values));
      }, 500);
    }

    return () => {
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, [key, values]);

  const clearDraft = () => {
    if (key) {
      sessionStorage.removeItem(key);
    }
  };

  return { clearDraft };
}
