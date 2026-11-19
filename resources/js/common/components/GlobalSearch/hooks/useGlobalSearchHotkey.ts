import { useEffect } from 'react';

interface UseGlobalSearchHotkeyProps {
  onOpenChange: (open: boolean) => void;
}

/**
 * Meta+K should open the global search dialog.
 */
export function useGlobalSearchHotkey({ onOpenChange }: UseGlobalSearchHotkeyProps) {
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const hasProperModifier = (e.metaKey || e.ctrlKey) && !e.altKey && !e.shiftKey;
      if (!hasProperModifier) { return; }

      const isAsciiLetter = /^[a-zA-Z]$/.test(e.key);
      const isSearchKey = isAsciiLetter
        ? e.key.toLowerCase() === 'k'
        : e.code === 'KeyK';

      if (isSearchKey) {
        e.preventDefault();
        onOpenChange(true);
      }
    };

    document.addEventListener('keydown', handleKeyDown);

    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [onOpenChange]);
}
