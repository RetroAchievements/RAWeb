import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAngry, LuFrown, LuLaugh, LuMeh, LuSmile } from 'react-icons/lu';

import { BaseButton } from '../../+vendor/BaseButton';
import { BaseDialogClose, BaseDialogFooter } from '../../+vendor/BaseDialog';
import {
  BaseForm,
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '../../+vendor/BaseForm';
import { BaseSeparator } from '../../+vendor/BaseSeparator';
import { BaseTextarea } from '../../+vendor/BaseTextarea';
import { BaseToggleGroup, BaseToggleGroupItem } from '../../+vendor/BaseToggleGroup';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipPortal,
  BaseTooltipTrigger,
} from '../../+vendor/BaseTooltip';
import { useBetaFeedbackForm } from './useBetaFeedbackForm';

interface BetaFeedbackFormProps {
  betaName: string;
  onSubmitSuccess: () => void;
}

export const BetaFeedbackForm: FC<BetaFeedbackFormProps> = ({ betaName, onSubmitSuccess }) => {
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useBetaFeedbackForm(betaName, onSubmitSuccess);

  return (
    <BaseForm {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        name="beta-feedback"
        className="flex flex-col gap-4"
      >
        <BaseFormField
          control={form.control}
          name="rating"
          render={({ field }) => (
            <BaseFormItem>
              <BaseFormLabel>{t('How satisfied are you with the new page design?')}</BaseFormLabel>

              <BaseFormControl>
                <div className="rounded-lg bg-neutral-950 p-4 light:border light:border-neutral-200 light:bg-white">
                  <BaseToggleGroup
                    type="single"
                    value={field.value || ''}
                    onValueChange={field.onChange}
                  >
                    <BaseToggleGroupItem
                      value="1"
                      aria-label={t('Strongly Dislike')}
                      className="hover:text-red-600 data-[state=on]:text-red-600"
                    >
                      <BaseTooltip>
                        <BaseTooltipTrigger hasHelpCursor={false} asChild>
                          <span>
                            <LuAngry className="size-8" />
                          </span>
                        </BaseTooltipTrigger>
                        <BaseTooltipPortal>
                          <BaseTooltipContent>{t('Strongly Dislike')}</BaseTooltipContent>
                        </BaseTooltipPortal>
                      </BaseTooltip>
                    </BaseToggleGroupItem>

                    <BaseToggleGroupItem
                      value="2"
                      aria-label={t('Dislike')}
                      className="hover:text-orange-600 data-[state=on]:text-orange-600"
                    >
                      <BaseTooltip>
                        <BaseTooltipTrigger hasHelpCursor={false} asChild>
                          <span>
                            <LuFrown className="size-8" />
                          </span>
                        </BaseTooltipTrigger>
                        <BaseTooltipPortal>
                          <BaseTooltipContent>{t('Dislike')}</BaseTooltipContent>
                        </BaseTooltipPortal>
                      </BaseTooltip>
                    </BaseToggleGroupItem>

                    <BaseToggleGroupItem
                      value="3"
                      aria-label={t('Neutral')}
                      className="hover:text-blue-500 data-[state=on]:text-blue-500"
                    >
                      <BaseTooltip>
                        <BaseTooltipTrigger hasHelpCursor={false} asChild>
                          <span>
                            <LuMeh className="size-8" />
                          </span>
                        </BaseTooltipTrigger>
                        <BaseTooltipPortal>
                          <BaseTooltipContent>{t('Neutral')}</BaseTooltipContent>
                        </BaseTooltipPortal>
                      </BaseTooltip>
                    </BaseToggleGroupItem>

                    <BaseToggleGroupItem
                      value="4"
                      aria-label={t('Like')}
                      className="hover:text-green-600 data-[state=on]:text-green-600"
                    >
                      <BaseTooltip>
                        <BaseTooltipTrigger hasHelpCursor={false} asChild>
                          <span>
                            <LuSmile className="size-8" />
                          </span>
                        </BaseTooltipTrigger>
                        <BaseTooltipPortal>
                          <BaseTooltipContent>{t('Like')}</BaseTooltipContent>
                        </BaseTooltipPortal>
                      </BaseTooltip>
                    </BaseToggleGroupItem>

                    <BaseToggleGroupItem
                      value="5"
                      aria-label={t('Strongly Like')}
                      className="hover:text-lime-600 data-[state=on]:text-lime-600"
                    >
                      <BaseTooltip>
                        <BaseTooltipTrigger hasHelpCursor={false} asChild>
                          <span>
                            <LuLaugh className="size-8" />
                          </span>
                        </BaseTooltipTrigger>
                        <BaseTooltipPortal>
                          <BaseTooltipContent>{t('Strongly Like')}</BaseTooltipContent>
                        </BaseTooltipPortal>
                      </BaseTooltip>
                    </BaseToggleGroupItem>
                  </BaseToggleGroup>
                </div>
              </BaseFormControl>
            </BaseFormItem>
          )}
        />

        <BaseSeparator className="my-4" />

        <BaseFormField
          control={form.control}
          name="positiveFeedback"
          render={({ field }) => (
            <BaseFormItem>
              <BaseFormLabel>{t("What's better than before?")}</BaseFormLabel>

              <BaseFormControl>
                <BaseTextarea {...field} rows={3} />
              </BaseFormControl>
            </BaseFormItem>
          )}
        />

        <BaseFormField
          control={form.control}
          name="negativeFeedback"
          render={({ field }) => (
            <BaseFormItem>
              <BaseFormLabel>{t('What still needs work?')}</BaseFormLabel>

              <BaseFormControl>
                <BaseTextarea {...field} rows={3} />
              </BaseFormControl>
            </BaseFormItem>
          )}
        />

        <BaseDialogFooter>
          <BaseDialogClose asChild>
            <BaseButton type="button" variant="link">
              {t('Maybe later')}
            </BaseButton>
          </BaseDialogClose>

          <BaseButton type="submit" disabled={mutation.isPending}>
            {t('Submit')}
          </BaseButton>
        </BaseDialogFooter>
      </form>
    </BaseForm>
  );
};
