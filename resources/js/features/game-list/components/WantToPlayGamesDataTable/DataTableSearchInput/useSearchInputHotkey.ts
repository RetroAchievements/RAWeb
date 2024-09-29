import { useRef } from 'react';
import { useKeyPressEvent } from 'react-use';

interface UseSearchInputHotkeyProps {
  key: Parameters<typeof useKeyPressEvent>[0];
}

export function useSearchInputHotkey({ key }: UseSearchInputHotkeyProps) {
  // Attach this to the input.
  const hotkeyInputRef = useRef<HTMLInputElement>(null);

  // When the user presses the given key on the keyboard, auto-focus the input.
  useKeyPressEvent(key, (event) => {
    if (hotkeyInputRef.current) {
      event.preventDefault(); // Don't automatically insert the given key into the input.
      hotkeyInputRef.current.focus();
    }
  });

  return { hotkeyInputRef };
}
