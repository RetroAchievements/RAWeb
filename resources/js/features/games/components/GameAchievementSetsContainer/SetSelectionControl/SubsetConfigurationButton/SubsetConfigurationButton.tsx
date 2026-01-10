import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuSettings2 } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';

import { SubsetConfigurationDialogContent } from './SubsetConfigurationDialogContent';

export const SubsetConfigurationButton: FC = () => {
  const { auth, game, selectableGameAchievementSets } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [isDialogOpen, setIsDialogOpen] = useState(false);

  // Only show the button if there are configurable sets (non-core, non-"will_be_*", non-exclusive).
  const configurableSets = selectableGameAchievementSets.filter((set) => {
    const setType = set.type;

    return !setType.startsWith('will_be_') && setType !== 'exclusive';
  });

  const isStandaloneSystem = game.system?.id === 102;
  const onlyHasCoreSet = configurableSets.length === 1;

  if (isStandaloneSystem || onlyHasCoreSet || !auth?.user) {
    return null;
  }

  const handleClick = () => {
    setIsDialogOpen(true);
  };

  const handleSubmitSuccess = () => {
    setIsDialogOpen(false);
    window.location.reload();
  };

  return (
    <>
      <BaseTooltip>
        <BaseTooltipTrigger asChild>
          <BaseButton
            size="icon"
            className="size-8 min-w-8"
            aria-label={t('Subset Configuration')}
            onClick={handleClick}
          >
            <LuSettings2 className="size-4" />
          </BaseButton>
        </BaseTooltipTrigger>

        <BaseTooltipContent>{t('Subset Configuration')}</BaseTooltipContent>
      </BaseTooltip>

      <BaseDialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <SubsetConfigurationDialogContent
          configurableSets={configurableSets}
          onSubmitSuccess={handleSubmitSuccess}
        />
      </BaseDialog>
    </>
  );
};
