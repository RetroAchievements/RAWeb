import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { SiPatreon } from 'react-icons/si';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import {
  BaseCard,
  BaseCardDescription,
  BaseCardFooter,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

export const BecomePatronCard: FC = () => {
  const { config } = usePageProps();
  const { t } = useTranslation();

  if (!config.services.patreon.userId) {
    return null;
  }

  return (
    <BaseCard className="max-w-md">
      <BaseCardHeader>
        <div className="flex justify-center">
          <div className="rounded-full bg-[#F96854] p-4">
            <SiPatreon className="size-8 text-white" />
          </div>
        </div>

        <BaseCardTitle className="text-balance text-center text-xl">
          {t('Thank you to all our amazing Patreon supporters')}
        </BaseCardTitle>
        <BaseCardDescription className="flex flex-col gap-2">
          <span className="text-balance text-center">
            <Trans
              i18nKey="This is a passion project. Donating is appreciated, but <1>never</1> required."
              components={{ 1: <span className="underline" /> }}
            />
          </span>
          <span className="text-balance text-center text-neutral-300 light:text-neutral-700">
            {t('Your contribution goes directly towards helping keep the servers alive.')}
          </span>
        </BaseCardDescription>
      </BaseCardHeader>

      <BaseCardFooter>
        <a
          href={`https://www.patreon.com/bePatron?u=${config.services.patreon.userId}`}
          className={baseButtonVariants({ size: 'sm', className: 'w-full' })}
        >
          <SiPatreon className={cn('mr-2 h-4 w-4 text-[#F96854]')} />
          {t('Become a Patron')}
        </a>
      </BaseCardFooter>
    </BaseCard>
  );
};
