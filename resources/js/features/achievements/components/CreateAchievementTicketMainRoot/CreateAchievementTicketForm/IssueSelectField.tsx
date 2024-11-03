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
import { Trans } from '@/common/components/Trans';

import type { CreateAchievementTicketFormValues } from './useCreateAchievementTicketForm';

export const IssueSelectField: FC = () => {
  const { t } = useLaravelReactI18n();

  const form = useFormContext<CreateAchievementTicketFormValues>();

  return (
    <BaseFormField
      control={form.control}
      name="issue"
      render={({ field }) => (
        <BaseFormItem className="flex w-full flex-col gap-1 sm:flex-row sm:items-center">
          <BaseFormLabel
            htmlFor="issue-select"
            className="text-menu-link sm:mt-[13px] sm:min-w-36 sm:self-start"
          >
            {t('Issue')}
          </BaseFormLabel>

          <div className="flex w-full flex-col gap-1">
            <BaseFormControl>
              <BaseSelect onValueChange={field.onChange} defaultValue={field.value ?? undefined}>
                <BaseSelectTrigger id="issue-select" className="sm:w-full md:w-96">
                  <BaseSelectValue placeholder={t('Select an issue')} />
                </BaseSelectTrigger>

                <BaseSelectContent>
                  <BaseSelectItem value="DidNotTrigger">{t('Did not trigger')}</BaseSelectItem>
                  <BaseSelectItem value="TriggeredAtWrongTime">
                    {t('Triggered at the wrong time')}
                  </BaseSelectItem>
                  <BaseSelectItem value="NetworkIssue">
                    {t('Triggered, but not showing as earned on site')}
                  </BaseSelectItem>
                </BaseSelectContent>
              </BaseSelect>
            </BaseFormControl>

            {field.value ? (
              <BaseFormDescription className="text-neutral-500 light:text-neutral-800">
                {field.value === 'DidNotTrigger' && (
                  <>
                    <span className="block">
                      {t("You completed the achievement's requirement, but it didn't unlock.")}
                    </span>
                    <span className="block">
                      {t(
                        "Example: The achievement requires collecting 10 coins. You collected 10 coins, but the achievement didn't unlock.",
                      )}
                    </span>
                  </>
                )}

                {field.value === 'TriggeredAtWrongTime' && (
                  <>
                    <span className="block">
                      {t(
                        "The achievement unlocked unexpectedly, even though you didn't complete its requirement.",
                      )}
                    </span>
                    <span className="block">
                      {t(
                        'Example: The achievement requires collecting 10 coins. You collected 5 coins, and it unlocked.',
                      )}
                    </span>
                  </>
                )}

                {field.value === 'NetworkIssue' && (
                  <>
                    <span className="block font-bold text-text-danger">
                      {t('Please do not create a ticket for this issue.')}
                    </span>
                    <span className="block">
                      <Trans i18nKey="If the achievement unlocked in your emulator but doesn't appear as unlocked on the website, this is usually caused by network issues. You can request a manual unlock on the <0>RetroAchievements Discord server</0>. Include a screenshot or some other form of proof.">
                        {/* eslint-disable */}
                        If the achievement unlocked in your emulator but doesn't appear as unlocked
                        on the website, this is usually caused by network issues. You can request a
                        manual unlock on the <a href="https://discord.com/invite/retroachievements">RetroAchievements Discord server</a>.
                        Include a screenshot or some other form of proof.
                        {/* eslint-enable */}
                      </Trans>
                    </span>
                  </>
                )}
              </BaseFormDescription>
            ) : null}
          </div>
        </BaseFormItem>
      )}
    />
  );
};
