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
    const handleKeyDown = (event: KeyboardEvent) => {
      if (!isEnabled || !formRef.current) {
        return;
      }

      // Only submit if the current focus is within the form.
      const isWithinForm = formRef.current.contains(document.activeElement);

      if (isWithinForm && (event.metaKey || event.ctrlKey) && event.code === 'Enter') {
        event.preventDefault();
        onSubmit();
      }
    };

    document.addEventListener('keydown', handleKeyDown);

    // Don't leak memory.
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [formRef, onSubmit, isEnabled]);
}
