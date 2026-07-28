import type { FC, ReactNode } from 'react';

import { BaseChip } from '@/common/components/+vendor/BaseChip';

interface CommentMetaChipProps {
  children: ReactNode;
}

export const CommentMetaChip: FC<CommentMetaChipProps> = ({ children }) => {
  return (
    <BaseChip className="text-2xs whitespace-nowrap text-neutral-300 light:bg-white light:text-neutral-900">
      {children}
    </BaseChip>
  );
};
