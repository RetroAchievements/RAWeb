import type { FC } from 'react';
import { useFormContext } from 'react-hook-form';
import { Trans, useTranslation } from 'react-i18next';
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
import { cn } from '@/common/utils/cn';

import type { CreateAchievementTicketFormValues } from './useCreateAchievementTicketForm';

export const DescriptionField: FC = () => {
  const { t } = useTranslation();

  const form = useFormContext<CreateAchievementTicketFormValues>();

  const [description] = form.watch(['description']);

  const showTriggerWarning =
    (description.length < 25 && /(n'?t|not?).*(work|trigger)/gi.test(description)) ||
    form.formState.errors.description?.type === 'too_small';

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
              <Trans
                i18nKey="<1>Be very descriptive</1> about what you were doing when the problem happened. Mention if you were using any <2>non-default settings, a non-English language, in-game cheats, glitches</2> or were otherwise playing in some unusual way. If possible, include a <3>link to a save state or save game</3> to help us reproduce the issue."
                components={{
                  1: <span className="text-neutral-300 light:text-neutral-950" />,
                  2: <span className="text-neutral-300 light:text-neutral-950" />,
                  3: <span className="text-neutral-300 light:text-neutral-950" />,
                }}
              />
            </BaseFormDescription>

            <BaseFormMessage className="mt-2">
              {showTriggerWarning
                ? t(
                    "Please be more specific with your issue—such as by adding specific reproduction steps or what you did before encountering it—instead of simply stating that it didn't work. The more specific, the better.",
                  )
                : null}

              {showNetworkWarning ? (
                <Trans
                  i18nKey="Please do not open tickets for network issues. See <1>here</1> for instructions on how to request a manual unlock."
                  components={{
                    1: (
                      // eslint-disable-next-line jsx-a11y/anchor-has-content -- this is passed in by the consumer
                      <a href="https://docs.retroachievements.org/general/faq.html#how-do-i-request-a-manual-unlock" />
                    ),
                  }}
                />
              ) : null}
            </BaseFormMessage>
          </div>
        </BaseFormItem>
      )}
    />
  );
};
