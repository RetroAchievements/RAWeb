import { RCheevos } from 'rcheevos';
import type { RefObject } from 'react';
import { useEffect, useRef } from 'react';

const promise = RCheevos.initialize();

export function useRcheevos(): RefObject<RCheevos | null> {
  const ref = useRef<RCheevos | null>(null);

  useEffect(() => {
    promise.then((instance) => {
      ref.current = instance;
    });
  }, []);

  return ref;
}
