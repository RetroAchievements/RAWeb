import { router } from '@inertiajs/react';
import { type FC, type FormEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuRefreshCcw } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialog,
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import { BaseSelectNative } from '@/common/components/+vendor/BaseSelectNative';
import { usePageProps } from '@/common/hooks/usePageProps';

export const ChangeInboxButton: FC = () => {
  const { auth, selectableInboxDisplayNames, senderUser } =
    usePageProps<App.Community.Data.MessageThreadIndexPageProps>();

  const { t } = useTranslation();

  const [selectedDisplayName, setSelectedDisplayName] = useState(senderUser?.displayName);

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (selectedDisplayName === auth!.user.displayName) {
      router.visit(route('message-thread.index'));
    } else {
      router.visit(route('message-thread.user.index', { user: selectedDisplayName }));
    }
  };

  return (
    <BaseDialog>
      <BaseDialogTrigger asChild>
        <BaseButton size="sm">
          <LuRefreshCcw className="mr-1.5 size-4" />
          {t('Change Inbox')}
        </BaseButton>
      </BaseDialogTrigger>

      <BaseDialogContent shouldShowCloseButton={false}>
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Change Inbox')}</BaseDialogTitle>
          <BaseDialogDescription>
            {t("You can view your own inbox or inboxes belonging to any of your teams' accounts.")}
          </BaseDialogDescription>
        </BaseDialogHeader>

        <form onSubmit={handleSubmit}>
          <div className="my-3 flex flex-col gap-1">
            <BaseLabel className="text-neutral-300 light:text-neutral-700" htmlFor="account-select">
              {t('Select an account')}
            </BaseLabel>
            <BaseSelectNative
              id="account-select"
              value={selectedDisplayName}
              onChange={(event) => setSelectedDisplayName(event.target.value)}
            >
              {selectableInboxDisplayNames.map((displayName) => (
                <option key={`selectable-inbox-${displayName}`} value={displayName}>
                  {displayName}
                </option>
              ))}
            </BaseSelectNative>
          </div>

          <BaseDialogFooter>
            <BaseDialogClose asChild>
              <BaseButton variant="link" type="button">
                {t('Cancel')}
              </BaseButton>
            </BaseDialogClose>

            <BaseButton type="submit">{t('Confirm')}</BaseButton>
          </BaseDialogFooter>
        </form>
      </BaseDialogContent>
    </BaseDialog>
  );
};
