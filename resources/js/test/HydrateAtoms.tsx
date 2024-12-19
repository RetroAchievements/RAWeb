import { useHydrateAtoms } from 'jotai/utils';
import type { FC, ReactNode } from 'react';

interface HydrateAtomsProps {
  children: ReactNode;
  initialValues: Parameters<typeof useHydrateAtoms>[0];
}

export const HydrateAtoms: FC<HydrateAtomsProps> = ({ children, initialValues }) => {
  useHydrateAtoms(initialValues);

  return children;
};
