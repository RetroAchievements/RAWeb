import { useMutation } from '@tanstack/react-query';
import type { AxiosResponse } from 'axios';
import axios from 'axios';
import { type FC, useState } from 'react';
import { LuAlertCircle, LuCopy } from 'react-icons/lu';
import { useCopyToClipboard, useMedia } from 'react-use';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { SimpleTooltip } from '@/common/components/SimpleTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';

export const ManageWebApiKey: FC = () => {
  const { userSettings } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const [, copyToClipboard] = useCopyToClipboard();

  // Hide the copy button's tooltip on mobile.
  const isXs = useMedia('(max-width: 640px)', true);

  const [currentWebApiKey, setCurrentWebApiKey] = useState(userSettings.apiKey ?? '');

  const mutation = useMutation({
    mutationFn: () => {
      return axios.delete<unknown, AxiosResponse<{ newKey: string }>>(
        route('settings.keys.web.destroy'),
      );
    },
    onSuccess: ({ data }) => {
      setCurrentWebApiKey(data.newKey);
    },
  });

  const handleCopyApiKeyClick = () => {
    copyToClipboard(currentWebApiKey);
    toastMessage.success('Copied your web API key!');
  };

  const handleResetApiKeyClick = () => {
    if (!confirm('Are you sure you want to reset your web API key? This cannot be reversed.')) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(), {
      loading: 'Resetting...',
      success: 'Your web API key has been reset.',
      error: 'Something went wrong.',
    });
  };

  return (
    <div className="@container">
      <div className="flex w-full flex-col @lg:grid @lg:grid-cols-4">
        <p className="w-48 text-menu-link">Web API Key</p>

        <div className="col-span-3 flex w-full flex-col gap-2">
          <SimpleTooltip
            isOpen={isXs ? false : undefined}
            isWrappingTapTarget={true}
            tooltipContent="Copy to clipboard"
          >
            <BaseButton
              className="flex gap-2 md:max-w-fit md:px-12"
              onClick={handleCopyApiKeyClick}
            >
              <LuCopy />
              <span className="font-mono">{safeFormatApiKey(currentWebApiKey)}</span>
            </BaseButton>
          </SimpleTooltip>

          <div>
            <p>
              This is your <span className="italic">personal</span> web API key. Handle it with
              care.
            </p>
            <p>
              The RetroAchievements API documentation can be found{' '}
              <a href="https://api-docs.retroachievements.org" target="_blank" rel="noreferrer">
                here
              </a>
              .
            </p>
          </div>

          <BaseButton
            className="flex w-full gap-2 @lg:max-w-fit"
            size="sm"
            variant="destructive"
            onClick={handleResetApiKeyClick}
          >
            <LuAlertCircle className="h-4 w-4" />
            Reset Web API Key
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
