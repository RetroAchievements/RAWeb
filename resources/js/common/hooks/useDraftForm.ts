import { useEffect, useRef, useState } from 'react';
import type { DefaultValues, FieldValues, UseFormProps } from 'react-hook-form';
import { useForm } from 'react-hook-form';

import { usePageProps } from './usePageProps';

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
  const { auth } = usePageProps();

  // sessionStorage outlives a logout and login in the same tab, so an unscoped
  // key would hand one user's unsubmitted content to whoever signs in next.
  // A null key disables persistence entirely, including for signed-out visitors.
  const draftKey = key && auth ? `${key}-${auth.user.id}` : null;

  // useForm() only reads defaultValues on mount, so read the draft lazily once.
  const [initialDraft] = useState(() => readDraft<T>(draftKey, excludeFromDraft));

  const form = useForm<T>({
    ...formProps,
    defaultValues: { ...defaultValues, ...initialDraft },
  });

  const previousKeyRef = useRef(draftKey);

  useEffect(() => {
    if (!draftKey) {
      return;
    }

    // useForm() only reads defaultValues on mount, so rehydrate by hand.
    if (previousKeyRef.current !== draftKey) {
      form.reset({ ...defaultValues, ...readDraft<T>(draftKey, excludeFromDraft) });
    }
    previousKeyRef.current = draftKey;

    const writeDraft = () => {
      if (isPristine(form.getValues(), defaultValues)) {
        removeDraft(draftKey);
      } else {
        storeDraft(draftKey, form.getValues());
      }
    };

    // Subscribing keeps keystrokes from re-rendering the whole form component.
    let timer: ReturnType<typeof setTimeout> | undefined;
    const unsubscribe = form.subscribe({
      formState: { values: true },
      callback: () => {
        clearTimeout(timer);
        timer = setTimeout(writeDraft, 500);
      },
    });

    return () => {
      clearTimeout(timer);
      unsubscribe();
      writeDraft();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps -- persistence is keyed on the draft key alone and reads everything else at write time
  }, [draftKey]);

  const clearDraft = () => {
    if (draftKey) {
      removeDraft(draftKey);
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

  let draft: Partial<T>;
  try {
    const saved = sessionStorage.getItem(key);
    draft = saved ? JSON.parse(saved) : {};
  } catch {
    // A corrupt or unreadable draft is not worth breaking the form over.
    return {};
  }

  for (const name of excludeFromDraft) {
    delete draft[name];
  }

  return draft;
}

function storeDraft(key: string, values: unknown): void {
  try {
    sessionStorage.setItem(key, JSON.stringify(values));
  } catch {
    // Persisting drafts is best-effort. Storage can be full or unavailable.
  }
}

function removeDraft(key: string): void {
  try {
    sessionStorage.removeItem(key);
  } catch {
    // Nothing to clean up if storage is unavailable.
  }
}

function isPristine<T extends FieldValues>(values: unknown, defaultValues: DefaultValues<T>) {
  const current = values as Record<string, unknown>;
  const defaults = defaultValues as Record<string, unknown>;

  const names = new Set([...Object.keys(current), ...Object.keys(defaults)]);

  return [...names].every(
    (name) => JSON.stringify(current[name]) === JSON.stringify(defaults[name]),
  );
}
