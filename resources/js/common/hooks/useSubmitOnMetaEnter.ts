import { type RefObject, useEffect } from 'react';

interface UseSubmitOnMetaEnterProps {
  formRef: RefObject<HTMLFormElement | null>;
  onSubmit: () => void;

  isEnabled?: boolean;
}

/**
 * Cmd+Enter (or Ctrl+Enter on Windows) should submit the form when focus is within the form.
 */
export function useSubmitOnMetaEnter({
  formRef,
  onSubmit,
  isEnabled = true,
}: UseSubmitOnMetaEnterProps) {
  useEffect(() => {
    const form = formRef.current;
    if (!isEnabled || !form) {
      return;
    }

    const handleKeyDown = (event: KeyboardEvent) => {
      if ((event.metaKey || event.ctrlKey) && event.code === 'Enter') {
        event.preventDefault();
        onSubmit();
      }
    };

    // Keyboard events bubble from focused children up to the form,
    // so listening on the form itself scopes the shortcut naturally.
    form.addEventListener('keydown', handleKeyDown);

    return () => form.removeEventListener('keydown', handleKeyDown);
  }, [formRef, onSubmit, isEnabled]);
}
