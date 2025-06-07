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
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        onOpenChange(true);
      }
    };

    document.addEventListener('keydown', handleKeyDown);

    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [onOpenChange]);
}
