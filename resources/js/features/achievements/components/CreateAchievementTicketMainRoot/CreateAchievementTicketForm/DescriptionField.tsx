import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { useFormContext } from 'react-hook-form';
import TextareaAutosize from 'react-textarea-autosize';

import {
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { baseTextareaClassNames } from '@/common/components/+vendor/BaseTextarea';
import { Trans } from '@/common/components/Trans';
import { cn } from '@/utils/cn';

import type { CreateAchievementTicketFormValues } from './useCreateAchievementTicketForm';

export const DescriptionField: FC = () => {
  const { t } = useLaravelReactI18n();

  const form = useFormContext<CreateAchievementTicketFormValues>();

  const [description] = form.watch(['description']);

  const showTriggerWarning =
    (description.length < 25 && /(n'?t|not?).*(work|trigger)/gi.test(description)) ||
    form.formState.errors.description?.type === 'too_small' ||
    form.formState.errors.description?.message === 'BE_MORE_DESCRIPTIVE';

  const showNetworkWarning = /(manual\s+unlock|internet)/gi.test(description);

  return (
    <BaseFormField
      control={form.control}
      name="description"
      render={({ field }) => (
        <BaseFormItem className="flex w-full flex-col gap-1 sm:flex-row sm:items-center">
          <BaseFormLabel className="text-menu-link sm:mt-[13px] sm:min-w-36 sm:self-start">
            {t('Description')}
          </BaseFormLabel>

          <div className="flex w-full flex-col gap-1">
            <BaseFormControl>
              <TextareaAutosize
                placeholder={t(
                  'Be as descriptive as possible. Give exact steps to reproduce the issue. Consider linking to a save state.',
                )}
                minRows={10}
                maxLength={2000}
                className={cn(baseTextareaClassNames, 'w-full')}
                {...field}
              />
            </BaseFormControl>

            <BaseFormDescription className="!text-neutral-500 light:text-neutral-400">
              <Trans i18nKey="<0>Be <1>very</1> descriptive</0> about what you were doing when the problem happened. Mention if you were using any <2>non-default settings, a non-English language, in-game cheats, glitches</2> or were otherwise playing in some unusual way. If possible, include a <3>link to a save state or save game</3> to help us reproduce the issue.">
                {/* eslint-disable */}
                <span className="text-neutral-300 light:text-neutral-950">
                  Be <span className="italic">very</span> descriptive
                </span>{' '}
                about what you were doing when the problem happened. Mention if you were using any{' '}
                <span className="text-neutral-300 light:text-neutral-950">
                  non-default settings, a non-English language, in-game cheats, glitches,
                </span>{' '}
                or were otherwise playing in some unusual way. If possible, include a{' '}
                <span className="text-neutral-300 light:text-neutral-950">link to a save state or save game</span> to help
                us reproduce the issue.
                {/* eslint-enable */}
              </Trans>
            </BaseFormDescription>

            <BaseFormMessage className="mt-2">
              {showTriggerWarning
                ? t(
                    "Please be more specific with your issue—such as by adding specific reproduction steps or what you did before encountering it—instead of simply stating that it didn't work. The more specific, the better.",
                  )
                : null}

              {showNetworkWarning ? (
                <Trans i18nKey="Please do not open tickets for network issues. See <0>here</0> for instructions on how to request a manual unlock.">
                  {/* eslint-disable */}
                  Please do not open tickets for network issues. See{' '}
                  <a href="https://docs.retroachievements.org/general/faq.html#how-do-i-request-a-manual-unlock">
                    here
                  </a>{' '}
                  for instructions on how to request a manual unlock.
                  {/* eslint-enable */}
                </Trans>
              ) : null}
            </BaseFormMessage>
          </div>
        </BaseFormItem>
      )}
    />
  );
};
