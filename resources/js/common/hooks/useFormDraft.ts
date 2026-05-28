import { useEffect, useRef } from 'react';
import type { DefaultValues, FieldValues, UseFormReturn } from 'react-hook-form';
import { useWatch } from 'react-hook-form';
import { loadDraft } from '@/common/utils/loadDraft';

/**
 * Persists form values to sessionStorage so drafts survive back-button
 * navigation and accidental page leaves/refreshes. Writes are debounced
 * to avoid blocking the main thread on every keystroke in large text fields.
 * Pass null as a key to disable persistence (eg: when editing, not creating).
 */
export function useFormDraft<T extends FieldValues>(
  key: string | null,
  form: UseFormReturn<T>,
  baseDefaults: DefaultValues<T> = {} as DefaultValues<T>,
) {
  const values = useWatch({ control: form.control });

  const valuesRef = useRef(values);
  valuesRef.current = values;

  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const isClearedRef = useRef(false);
  const isFirstRender = useRef(true);

  useEffect(() => {
    if (isFirstRender.current) {
      isFirstRender.current = false;
      return;
    }

    if (key) {
      isClearedRef.current = false;
      const draft = loadDraft<T>(key);
      
      if (JSON.stringify(form.getValues()) !== JSON.stringify({ ...baseDefaults, ...draft })) {
        form.reset({ ...baseDefaults, ...draft } as DefaultValues<T>);
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key]);

  useEffect(() => {
    if (key) {
      if (timerRef.current) clearTimeout(timerRef.current);

      timerRef.current = setTimeout(() => {
        if (isClearedRef.current && JSON.stringify(values) === JSON.stringify(baseDefaults)) {
          return;
        }

        sessionStorage.setItem(key, JSON.stringify(values));
        isClearedRef.current = false;
      }, 500);
    }

    return () => {
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, [key, values, baseDefaults]);

  useEffect(() => {
    return () => {
      if (key && !isClearedRef.current) {
        sessionStorage.setItem(key, JSON.stringify(valuesRef.current));
      }
    };
  }, [key]);

  const clearDraft = () => {
    if (key) {
      isClearedRef.current = true;
      sessionStorage.removeItem(key);
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    }
  };

  return { clearDraft };
}
