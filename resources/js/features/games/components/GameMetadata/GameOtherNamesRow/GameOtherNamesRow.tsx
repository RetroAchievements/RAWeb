import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';

interface GameOtherNamesRowProps {
  nonCanonicalTitles: string[];
}

export const GameOtherNamesRow: FC<GameOtherNamesRowProps> = ({ nonCanonicalTitles }) => {
  const { t } = useTranslation();

  // Determine how many titles to display based on the total count.
  const totalCount = nonCanonicalTitles.length;
  const displayLimit = totalCount <= 4 ? totalCount : 3;
  const displayedTitles = nonCanonicalTitles.slice(0, displayLimit);
  const hiddenTitles = nonCanonicalTitles.slice(displayLimit);
  const hiddenCount = hiddenTitles.length;

  return (
    <BaseTableRow className="first:rounded-t-lg last:rounded-b-lg">
      <BaseTableCell className="whitespace-nowrap text-right align-top">
        {t('metaOtherName', { count: nonCanonicalTitles.length })}
      </BaseTableCell>

      <BaseTableCell>
        <div className="flex flex-col">
          {displayedTitles.map((title, titleIndex) => (
            <span key={`title-${titleIndex}`}>{title}</span>
          ))}

          {hiddenCount > 0 ? (
            <BaseTooltip>
              <BaseTooltipTrigger asChild>
                <span className="max-w-fit text-neutral-400 underline decoration-dotted light:text-neutral-700">
                  {t('+{{val, number}} more', { val: hiddenCount })}
                </span>
              </BaseTooltipTrigger>

              <BaseTooltipContent className="max-w-xs">
                <div className="flex flex-col gap-1">
                  {hiddenTitles.map((title, titleIndex) => (
                    <span key={`hidden-title-${titleIndex}`}>{title}</span>
                  ))}
                </div>
              </BaseTooltipContent>
            </BaseTooltip>
          ) : null}
        </div>
      </BaseTableCell>
    </BaseTableRow>
  );
};
