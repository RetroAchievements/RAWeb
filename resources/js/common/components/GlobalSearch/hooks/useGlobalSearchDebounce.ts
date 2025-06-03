import type { Dispatch, SetStateAction } from 'react';
import { useRef } from 'react';
import { useDebounce } from 'react-use';

interface UseGlobalSearchDebounceProps {
  rawQuery: string;
  setSearchTerm: Dispatch<SetStateAction<string>>;
}

export function useGlobalSearchDebounce({ rawQuery, setSearchTerm }: UseGlobalSearchDebounceProps) {
  const isFirstRender = useRef(true);

  useDebounce(
    () => {
      // Skip the first render to avoid triggering search on mount.
      if (isFirstRender.current) {
        isFirstRender.current = false;

        return;
      }

      // Only trigger search if we have a meaningful query.
      if (rawQuery.length >= 3) {
        setSearchTerm(rawQuery);
      } else if (rawQuery.length === 0) {
        // Clear search immediately when input is cleared.
        setSearchTerm('');
      }
    },
    getDebounceDuration(rawQuery),
    [rawQuery],
  );
}

function getDebounceDuration(input: string): number {
  // Don't debounce at all when the input is cleared.
  if (input.length === 0) {
    return 0;
  }

  // Use a longer debounce for shorter inputs.
  if (input.length < 3) {
    return 1000;
  }

  // Use a shorter debounce for longer inputs.
  return 500;
}
