import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { BaseDialog, BaseDialogTrigger } from '../../+vendor/BaseDialog';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../../+vendor/BaseTooltip';
import { RaMissable } from '../../RaMissable';
import { RaProgression } from '../../RaProgression';
import { RaWinCondition } from '../../RaWinCondition';

interface AchievementTypeIndicatorProps {
  type: NonNullable<App.Platform.Data.Achievement['type']>;

  /** This element should be wrapped by <BaseDialogContent />. */
  dialogContent?: ReactNode;

  /** When true, renders the label text to the right of the icon. */
  showLabel?: boolean;

  wrapperClassName?: string;
}

export const AchievementTypeIndicator: FC<AchievementTypeIndicatorProps> = ({
  dialogContent,
  type,
  wrapperClassName,
  showLabel = false,
}) => {
  const { t } = useTranslation();

  const typeMetaMap: Record<
    AchievementTypeIndicatorProps['type'],
    { label: TranslatedString; icon: ReactNode }
  > = {
    missable: { icon: <RaMissable className="size-[18px]" />, label: t('Missable') },
    progression: { icon: <RaProgression className="size-[18px]" />, label: t('Progression') },
    win_condition: { icon: <RaWinCondition className="size-[18px]" />, label: t('Win Condition') },
  };

  const { icon, label } = typeMetaMap[type];

  if (dialogContent && (type === 'progression' || type === 'win_condition')) {
    return (
      <BaseDialog>
        <BaseDialogTrigger>
          <Indicator
            type={type}
            icon={icon}
            label={label}
            showLabel={showLabel}
            wrapperClassName={wrapperClassName}
          />
        </BaseDialogTrigger>

        {dialogContent}
      </BaseDialog>
    );
  }

  return (
    <Indicator
      type={type}
      icon={icon}
      label={label}
      showLabel={showLabel}
      wrapperClassName={wrapperClassName}
    />
  );
};

interface IndicatorProps {
  icon: ReactNode;
  label: TranslatedString;
  showLabel: boolean;
  type: AchievementTypeIndicatorProps['type'];

  wrapperClassName?: string;
}

const Indicator: FC<IndicatorProps> = ({ type, icon, label, showLabel, wrapperClassName }) => {
  const content = (
    <div
      data-testid={`type-${type}`}
      className={cn(
        'type-ind group',

        type === 'progression' || type === 'win_condition' ? 'cursor-pointer' : null,
        type === 'missable' ? 'border-dashed border-stone-500' : 'border-transparent',

        showLabel && 'flex items-center gap-1',

        wrapperClassName,
      )}
    >
      <div aria-label={label}>{icon}</div>

      {showLabel ? <span>{label}</span> : null}
    </div>
  );

  if (showLabel) {
    return content;
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>{content}</BaseTooltipTrigger>

      <BaseTooltipContent>{label}</BaseTooltipContent>
    </BaseTooltip>
  );
};
