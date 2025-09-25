import { type FC, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuCircleAlert, LuCopy } from 'react-icons/lu';
import { useCopyToClipboard } from 'react-use';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipPortal,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useResetWebApiKeyMutation } from '@/features/settings/hooks/mutations/useResetWebApiKeyMutation';

export const ManageWebApiKey: FC = () => {
  const { userSettings, ziggy } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const [, copyToClipboard] = useCopyToClipboard();

  const [currentWebApiKey, setCurrentWebApiKey] = useState(userSettings.apiKey ?? '');

  const mutation = useResetWebApiKeyMutation();

  const handleCopyApiKeyClick = () => {
    copyToClipboard(currentWebApiKey);
    toastMessage.success(t('Copied your web API key!'));
  };

  const handleResetApiKeyClick = () => {
    if (!confirm(t('Are you sure you want to reset your web API key? This cannot be reversed.'))) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(), {
      loading: t('Resetting...'),
      success: ({ data }) => {
        setCurrentWebApiKey(data.newKey);

        return t('Your web API key has been reset.');
      },
      error: t('Something went wrong.'),
    });
  };

  return (
    <div className="@container">
      <div className="flex w-full flex-col @lg:grid @lg:grid-cols-4">
        <p className="w-48 text-menu-link">{t('Web API Key')}</p>

        <div className="col-span-3 flex w-full flex-col gap-2">
          <BaseTooltip open={ziggy.device === 'mobile' ? false : undefined}>
            <BaseTooltipTrigger asChild>
              <BaseButton
                className="flex gap-2 md:max-w-fit md:px-12"
                onClick={handleCopyApiKeyClick}
              >
                <LuCopy />
                <span className="font-mono">{safeFormatApiKey(currentWebApiKey)}</span>
              </BaseButton>
            </BaseTooltipTrigger>

            <BaseTooltipPortal>
              <BaseTooltipContent>
                <p>{t('Copy to clipboard')}</p>
              </BaseTooltipContent>
            </BaseTooltipPortal>
          </BaseTooltip>

          <div>
            <p>
              <Trans
                i18nKey="This is your <1>personal</1> web API key. Handle it with care."
                components={{ 1: <span className="italic" /> }}
              />
            </p>
            <p>
              <Trans
                i18nKey="The RetroAchievements API documentation can be found <1>here</1>."
                components={{
                  1: (
                    // eslint-disable-next-line jsx-a11y/anchor-has-content -- this is passed in by the consumer
                    <a
                      href="https://api-docs.retroachievements.org"
                      target="_blank"
                      rel="noreferrer"
                    />
                  ),
                }}
              />
            </p>
          </div>

          <BaseButton
            className="flex w-full gap-2 @lg:max-w-fit"
            size="sm"
            variant="destructive"
            onClick={handleResetApiKeyClick}
          >
            <LuCircleAlert className="h-4 w-4" />
            {t('Reset Web API Key')}
          </BaseButton>
        </div>
      </div>
    </div>
  );
};

/**
 * If someone is sharing their screen, we don't want them
 * to accidentally leak their web API key.
 */
function safeFormatApiKey(apiKey: string): string {
  // For safety, but this should never happen.
  if (apiKey.length <= 12) {
    return apiKey;
  }

  // "AAAAAA...123456"
  return `${apiKey.slice(0, 6)}...${apiKey.slice(-6)}`;
}
