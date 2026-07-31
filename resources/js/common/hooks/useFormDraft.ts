import { useEffect, useRef } from 'react';
import type { DefaultValues, FieldValues, UseFormReturn } from 'react-hook-form';
import { useWatch } from 'react-hook-form';

import { loadDraft } from '@/common/utils/loadDraft';

/**
 * Persists form values to sessionStorage so drafts survive back-button
 * navigation, accidental page leaves/refreshes, and unmounts such as tab
 * switches. Writes are debounced to avoid blocking the main thread on every
 * keystroke in large text fields, then flushed when the form unmounts or
 * switches keys.
 * Pass null as a key to disable persistence (eg: when editing, not creating).
 */
export function useFormDraft<T extends FieldValues>(
  key: string | null,
  form: UseFormReturn<T>,
  baseDefaultValues: DefaultValues<T>,
) {
  const values = useWatch({ control: form.control });

  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pendingWriteRef = useRef<(() => void) | null>(null);
  const previousKeyRef = useRef(key);

  useEffect(() => {
    const previousKey = previousKeyRef.current;
    previousKeyRef.current = key;

    if (!key || key === previousKey) {
      return;
    }

    pendingWriteRef.current?.();
    form.reset({ ...baseDefaultValues, ...loadDraft<T>(key) });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- rehydration is keyed on the draft key alone
  }, [key]);

  useEffect(() => {
    if (!key) {
      return;
    }

    const writeDraft = () => {
      sessionStorage.setItem(key, JSON.stringify(values));
      pendingWriteRef.current = null;
    };

    pendingWriteRef.current = writeDraft;

    const timer = setTimeout(writeDraft, 500);
    timerRef.current = timer;

    return () => {
      clearTimeout(timer);
    };
  }, [key, values]);

  useEffect(() => {
    return () => {
      pendingWriteRef.current?.();
    };
  }, []);

  const clearDraft = () => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
    }
    pendingWriteRef.current = null;

    if (key) {
      sessionStorage.removeItem(key);
    }
  };

  return { clearDraft };
}
