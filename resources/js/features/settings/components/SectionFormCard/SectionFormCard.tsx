import type { FC, ReactNode } from 'react';
import type { UseFormReturn } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import type { BaseButtonProps } from '@/common/components/+vendor/BaseButton';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseCard,
  BaseCardContent,
  BaseCardFooter,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';
import { BaseFormProvider } from '@/common/components/+vendor/BaseForm';

export interface SectionFormCardProps {
  t_headingLabel: string;
  children: ReactNode;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any -- any is valid
  formMethods: UseFormReturn<any>;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any -- any is valid
  onSubmit: (formValues: any) => void;
  isSubmitting: boolean;

  buttonProps?: BaseButtonProps;
}

export const SectionFormCard: FC<SectionFormCardProps> = ({
  buttonProps,
  children,
  formMethods,
  isSubmitting,
  onSubmit,
  t_headingLabel,
}) => {
  const { t } = useTranslation();

  return (
    <BaseCard className="w-full">
      <BaseCardHeader className="pb-4">
        <BaseCardTitle>{t_headingLabel}</BaseCardTitle>
      </BaseCardHeader>

      <BaseFormProvider {...formMethods}>
        <form onSubmit={formMethods.handleSubmit(onSubmit)}>
          <BaseCardContent>{children}</BaseCardContent>

          <BaseCardFooter>
            <div className="flex w-full justify-end">
              <BaseButton
                type="submit"
                disabled={isSubmitting}
                data-testid={`${t_headingLabel}-submit`}
                {...buttonProps}
              >
                {buttonProps?.children ?? t('Update')}
              </BaseButton>
            </div>
          </BaseCardFooter>
        </form>
      </BaseFormProvider>
    </BaseCard>
  );
};
