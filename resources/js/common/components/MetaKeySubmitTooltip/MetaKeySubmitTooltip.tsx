import type { ComponentPropsWithoutRef, FC, ReactNode } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

interface BaseProps {
  children: ReactNode;
}

type MetaKeySubmitTooltipProps = BaseProps & ComponentPropsWithoutRef<typeof BaseTooltip>;

export const MetaKeySubmitTooltip: FC<MetaKeySubmitTooltipProps> = ({ children, ...rest }) => {
  const { metaKey } = usePageProps();

  if (!metaKey) {
    return children;
  }

  return (
    <BaseTooltip {...rest}>
      <BaseTooltipTrigger asChild>{children}</BaseTooltipTrigger>

      {/* Doesn't need to be localized. The 'Enter' key is universal. */}
      <BaseTooltipContent>{`${metaKey} + Enter`}</BaseTooltipContent>
    </BaseTooltip>
  );
};
