import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { useFormContext } from 'react-hook-form';

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

import type { CreateAchievementTicketFormValues } from './useCreateAchievementTicketForm';

export const GameHashSelectField: FC = () => {
  const { gameHashes } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();

  const { t } = useLaravelReactI18n();

  const form = useFormContext<CreateAchievementTicketFormValues>();

  const namedHashes = gameHashes.filter((hash) => !!hash.name?.trim());
  const unnamedHashes = gameHashes.filter((hash) => !hash.name?.trim());

  const sortedNamedHashes = [...namedHashes].sort((a, b) => a.name!.localeCompare(b.name!));
  const sortedUnnamedHashes = [...unnamedHashes].sort((a, b) => a.md5.localeCompare(b.md5));

  const sortedHashes = [...sortedNamedHashes, ...sortedUnnamedHashes];

  return (
    <BaseFormField
      control={form.control}
      name="hash"
      render={({ field }) => (
        <BaseFormItem className="flex w-full flex-col gap-1 sm:flex-row sm:items-center">
          <BaseFormLabel
            htmlFor="hash-select"
            className="text-menu-link sm:mt-[13px] sm:min-w-36 sm:self-start"
          >
            {t('Supported Game File')}
          </BaseFormLabel>

          <div className="flex w-full flex-col gap-1">
            <BaseFormControl>
              <BaseSelect
                onValueChange={field.onChange}
                defaultValue={field.value ? String(field.value) : undefined}
              >
                <BaseSelectTrigger id="hash-select">
                  <BaseSelectValue placeholder={t('Select a file')} />
                </BaseSelectTrigger>

                <BaseSelectContent>
                  {sortedHashes.map((hash) => (
                    <BaseSelectItem key={`hash-${hash.md5}`} value={String(hash.id)}>
                      {hash.name ? `${hash.md5} ${hash.name}` : hash.md5}
                    </BaseSelectItem>
                  ))}
                </BaseSelectContent>
              </BaseSelect>
            </BaseFormControl>

            <BaseFormDescription className="light:text-neutral-800">
              {t(
                'If you\'re using RetroArch, this can be found by entering the RetroArch menu while your ROM is loaded and scrolling down to "Information". It will be the long alphanumeric text after "RetroAchievements Hash".',
              )}
            </BaseFormDescription>
          </div>
        </BaseFormItem>
      )}
    />
  );
};
