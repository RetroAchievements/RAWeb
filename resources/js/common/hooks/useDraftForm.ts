import { useEffect, useRef } from 'react';
import type { DefaultValues, FieldValues, UseFormProps } from 'react-hook-form';
import { useForm, useWatch } from 'react-hook-form';

import { loadDraft } from '@/common/utils/loadDraft';

import { useUserScopedDraftKey } from './useUserScopedDraftKey';

interface UseDraftFormOptions<T extends FieldValues> extends UseFormProps<T> {
  defaultValues: DefaultValues<T>;

  /** Fields the draft holds but never restores, eg: a recipient the user should pick again. */
  excludeFromDraft?: Array<keyof T>;
}

/**
 * A form whose values are persisted to sessionStorage, so drafts survive
 * back-button navigation, accidental page leaves/refreshes, and unmounts such
 * as tab switches. Writes are debounced to avoid blocking the main thread on
 * every keystroke in large text fields, then flushed when the form unmounts or
 * switches keys. Drafts are namespaced per user.
 * Pass null as a key to disable persistence, eg: when editing, not creating.
 */
export function useDraftForm<T extends FieldValues>(
  key: string | null,
  { defaultValues, excludeFromDraft, ...formProps }: UseDraftFormOptions<T>,
) {
  const draftKey = useUserScopedDraftKey(key);

  const form = useForm<T>({
    ...formProps,
    defaultValues: { ...defaultValues, ...readDraft<T>(draftKey, excludeFromDraft) },
  });

  const values = useWatch({ control: form.control });

  const latestValuesRef = useRef(values);
  latestValuesRef.current = values;

  const previousKeyRef = useRef(draftKey);

  const writeDraft = (target: string) => {
    try {
      if (isPristine(latestValuesRef.current, defaultValues)) {
        sessionStorage.removeItem(target);
      } else {
        sessionStorage.setItem(target, JSON.stringify(latestValuesRef.current));
      }
    } catch {
      // Persisting drafts is best-effort. Storage can be full or unavailable.
    }
  };

  useEffect(() => {
    if (!draftKey) {
      return;
    }

    const timer = setTimeout(() => writeDraft(draftKey), 500);

    return () => clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- writeDraft reads the latest values through a ref
  }, [draftKey, values]);

  useEffect(() => {
    if (!draftKey) {
      return;
    }

    // useForm() only reads defaultValues on mount, so rehydrate by hand.
    if (previousKeyRef.current !== draftKey) {
      form.reset({ ...defaultValues, ...readDraft<T>(draftKey, excludeFromDraft) });
    }
    previousKeyRef.current = draftKey;

    return () => writeDraft(draftKey);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- rehydration is keyed on the draft key alone
  }, [draftKey]);

  const clearDraft = () => {
    if (draftKey) {
      try {
        sessionStorage.removeItem(draftKey);
      } catch {
        // Nothing to clean up if storage is unavailable.
      }
    }

    form.reset(defaultValues);
  };

  return { form, clearDraft };
}

function readDraft<T extends FieldValues>(
  key: string | null,
  excludeFromDraft: Array<keyof T> = [],
): Partial<T> {
  if (!key) {
    return {};
  }

  const draft = loadDraft<T>(key);
  for (const name of excludeFromDraft) {
    delete draft[name];
  }

  return draft;
}

function isPristine<T extends FieldValues>(values: unknown, defaultValues: DefaultValues<T>) {
  const current = values as Record<string, unknown>;
  const defaults = defaultValues as Record<string, unknown>;

  const names = new Set([...Object.keys(current), ...Object.keys(defaults)]);

  return [...names].every(
    (name) => JSON.stringify(current[name]) === JSON.stringify(defaults[name]),
  );
}
