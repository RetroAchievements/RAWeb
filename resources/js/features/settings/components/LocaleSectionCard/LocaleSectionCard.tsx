import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import {
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';
import { usePageProps } from '@/common/hooks/usePageProps';

import { SectionFormCard } from '../SectionFormCard';
import { useLocaleSectionForm } from './useLocaleSectionForm';

export const LocaleSectionCard: FC = () => {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useLocaleSectionForm({
    locale: auth?.user.locale?.length ? auth.user.locale : 'en_US',
  });

  return (
    <SectionFormCard
      t_headingLabel={t('Locale')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
    >
      <div className="flex flex-col gap-7 @container @xl:gap-5">
        <BaseFormField
          control={form.control}
          name="locale"
          render={({ field }) => (
            <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row">
              <BaseFormLabel htmlFor="locale-select" className="text-menu-link @xl:mt-4 @xl:w-2/5">
                {t('Current Locale')}
              </BaseFormLabel>

              <div className="flex flex-grow flex-col gap-1">
                <BaseFormControl>
                  <BaseSelect onValueChange={field.onChange} defaultValue={field.value}>
                    <BaseSelectTrigger id="locale-select">
                      <BaseSelectValue />
                    </BaseSelectTrigger>

                    <BaseSelectContent>
                      {/* These labels should not be localized. */}
                      <BaseSelectItem value="en_US">{'English (US)'}</BaseSelectItem>
                      <BaseSelectItem value="en_GB">{'English (UK)'}</BaseSelectItem>
                      <BaseSelectItem value="es_ES">{'Español (España)'}</BaseSelectItem>
                      <BaseSelectItem value="pl_PL">{'Polski (Polska)'}</BaseSelectItem>
                      <BaseSelectItem value="pt_BR">{'Português (Brasil)'}</BaseSelectItem>
                    </BaseSelectContent>
                  </BaseSelect>
                </BaseFormControl>

                <BaseFormDescription>
                  <Trans
                    i18nKey="Most of the website is still untranslated. If you'd like to help with translations, you can learn more about how to contribute <1>here</1>."
                    components={{
                      1: (
                        <a href="https://github.com/RetroAchievements/RAWeb/blob/master/docs/TRANSLATIONS.md" />
                      ),
                    }}
                  />
                </BaseFormDescription>
              </div>
            </BaseFormItem>
          )}
        />
      </div>
    </SectionFormCard>
  );
};
