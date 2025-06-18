import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';

interface GameOtherNamesRowProps {
  nonCanonicalTitles: string[];
}

export const GameOtherNamesRow: FC<GameOtherNamesRowProps> = ({ nonCanonicalTitles }) => {
  const { t } = useTranslation();

  return (
    <BaseTableRow className="first:rounded-t-lg last:rounded-b-lg">
      <BaseTableCell className="whitespace-nowrap text-right align-top">
        {t('metaOtherName', { count: nonCanonicalTitles.length })}
      </BaseTableCell>

      <BaseTableCell>
        <div className="flex flex-col">
          {nonCanonicalTitles.map((title, titleIndex) => (
            <span key={`title-${titleIndex}`}>{title}</span>
          ))}
        </div>
      </BaseTableCell>
    </BaseTableRow>
  );
};
