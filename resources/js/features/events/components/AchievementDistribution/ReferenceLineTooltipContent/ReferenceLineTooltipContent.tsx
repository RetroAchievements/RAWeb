import type { ComponentProps, FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseChartTooltipContent } from '@/common/components/+vendor/BaseChart';
import { cn } from '@/common/utils/cn';

type ReferenceLineTooltipContentProps = ComponentProps<typeof BaseChartTooltipContent> & {
  buckets: App.Platform.Data.PlayerAchievementChartBucket[];
  userAchievementCounts: { softcore: number | null; hardcore: number | null } | null;

  userHardcoreIndex?: number;
  userSoftcoreIndex?: number;
};

export const ReferenceLineTooltipContent: FC<ReferenceLineTooltipContentProps> = ({
  active,
  payload,
  buckets,
  userHardcoreIndex,
  userSoftcoreIndex,
  userAchievementCounts,
  ...rest
}) => {
  const { t } = useTranslation();

  if (!active || !payload?.length) {
    return <BaseChartTooltipContent active={active} payload={payload} {...rest} />;
  }

  // Get the current bucket index being hovered.
  const currentIndex = buckets.findIndex((bucket) => bucket === payload[0].payload);

  // Check if this bucket has a reference line.
  const hasHardcoreLine = userHardcoreIndex !== undefined && currentIndex === userHardcoreIndex;
  const hasSoftcoreLine = userSoftcoreIndex !== undefined && currentIndex === userSoftcoreIndex;

  // If no reference lines, just return the standard content.
  if (!hasHardcoreLine && !hasSoftcoreLine) {
    return <BaseChartTooltipContent active={active} payload={payload} {...rest} />;
  }

  // Return the enhanced content with reference line information.
  return (
    <div
      className={cn(
        'grid min-w-[196px] items-start gap-1.5 rounded-lg border border-neutral-600 bg-embed',
        'px-2.5 py-1.5 text-xs text-neutral-300 shadow-xl light:text-neutral-800',
      )}
    >
      {/* Render the standard tooltip content first. */}
      <BaseChartTooltipContent
        active={active}
        payload={payload}
        {...rest}
        className="!m-0 !border-0 !bg-transparent !p-0 !shadow-none"
      />

      {/* Add reference line information. */}
      <div className="mt-1.5 grid gap-1.5 border-t border-neutral-600/50 pt-1.5">
        {hasHardcoreLine && userAchievementCounts?.hardcore !== undefined && (
          <div className="flex items-center gap-1.5">
            <div
              className="my-0.5 h-3 w-0 shrink-0 border-[1.5px] border-dashed"
              style={{ borderColor: '#cc9900' }}
            />
            <span>{t('Your hardcore progress')}</span>
          </div>
        )}

        {hasSoftcoreLine && userAchievementCounts?.softcore !== undefined && (
          <div className="flex items-center gap-1.5">
            <div
              className="my-0.5 h-3 w-0 shrink-0 border-[1.5px] border-dashed"
              style={{ borderColor: '#737373' }}
            />
            <span>{t('Your softcore progress')}</span>
          </div>
        )}
      </div>
    </div>
  );
};
