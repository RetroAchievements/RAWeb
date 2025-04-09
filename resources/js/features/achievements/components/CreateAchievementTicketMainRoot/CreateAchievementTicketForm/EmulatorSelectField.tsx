import type { FC } from 'react';
import { useFormContext } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
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

export const EmulatorSelectField: FC = () => {
  const { emulators } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();

  const { t } = useTranslation();

  const form = useFormContext<CreateAchievementTicketFormValues>();
  const [emulator] = form.watch(['emulator']);

  const sortedEmulators = emulators.sort((a, b) =>
    a.name.startsWith('Other') ? 1 : b.name.startsWith('Other') ? -1 : a.name.localeCompare(b.name),
  );

  const selectedEmulator = emulators.find((e) => e.name === emulator);

  return (
    <BaseFormField
      control={form.control}
      name="emulator"
      render={({ field }) => (
        <BaseFormItem className="flex w-full flex-col gap-1 sm:flex-row sm:items-center">
          <BaseFormLabel
            htmlFor="emulator-select"
            className="w-36 text-menu-link sm:mt-[13px] sm:min-w-36 sm:self-start"
          >
            {t('Emulator')}
          </BaseFormLabel>

          <div className="flex w-full flex-col gap-1">
            <BaseFormControl>
              <BaseSelect
                onValueChange={field.onChange}
                defaultValue={field.value ? String(field.value) : undefined}
              >
                <BaseSelectTrigger id="emulator-select" className="sm:w-full md:w-96">
                  <BaseSelectValue placeholder={t('Select an emulator')} />
                </BaseSelectTrigger>

                <BaseSelectContent>
                  {sortedEmulators.map((emulator) => (
                    <BaseSelectItem
                      key={`emulator-${emulator.id}-${emulator.name}`}
                      value={emulator.name}
                    >
                      {emulator.name}
                    </BaseSelectItem>
                  ))}
                </BaseSelectContent>

                {selectedEmulator?.canDebugTriggers === false ? (
                  <BaseFormMessage>
                    {t(
                      'Developers may not be able to easily debug issues with this emulator. Please be extremely detailed in your description, and provide a link to a save game or video if possible.',
                    )}
                  </BaseFormMessage>
                ) : null}

                <BaseFormMessage>
                  {form.formState.errors.emulator?.message === 'Required' ? t('Required') : null}
                </BaseFormMessage>
              </BaseSelect>
            </BaseFormControl>
          </div>
        </BaseFormItem>
      )}
    />
  );
};
