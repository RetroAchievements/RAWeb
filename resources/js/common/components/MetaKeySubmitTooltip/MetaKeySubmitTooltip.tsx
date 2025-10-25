import type { ComponentPropsWithoutRef, FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

interface BaseProps {
  children: ReactNode;
}

type MetaKeySubmitTooltipProps = BaseProps & ComponentPropsWithoutRef<typeof BaseTooltip>;

export const MetaKeySubmitTooltip: FC<MetaKeySubmitTooltipProps> = ({ children, ...rest }) => {
  const { metaKey } = usePageProps();
  const { t } = useTranslation();

  if (!metaKey) {
    return children;
  }

  return (
    <BaseTooltip {...rest}>
      <BaseTooltipTrigger asChild>{children}</BaseTooltipTrigger>

      <BaseTooltipContent>
        {t('Submit ({{metaKey}}+Enter)', {
          metaKey,
          nsSeparator: null,
        })}
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
