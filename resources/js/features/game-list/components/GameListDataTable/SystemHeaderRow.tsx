import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';

interface SystemHeaderRowProps {
  /**
   * Total number of columns currently visible on the table.
   * This is used to ensure the header spans the full width of the table via colspan.
   */
  columnCount: number;

  /**
   * Number of games in this system's group.
   * When greater than 1, displays a "N games" label next to the system name.
   */
  gameCount: number;

  /**
   * Displayed as the main text of the header row.
   */
  systemName: string;
}

export const SystemHeaderRow: FC<SystemHeaderRowProps> = ({
  columnCount,
  gameCount,
  systemName,
}) => {
  const { t } = useTranslation();

  const groupDescription = t('rowGroupAriaLabel', { systemName, count: gameCount, val: gameCount });

  return (
    <BaseTableRow className="do-not-highlight" role="rowheader" aria-label={groupDescription}>
      <BaseTableCell
        colSpan={columnCount}
        className="bg-neutral-950 py-2 pl-3 light:bg-neutral-100 black:bg-neutral-900"
        role="columnheader"
        aria-colspan={columnCount}
      >
        <div className="flex items-center gap-1.5" role="group" aria-label={groupDescription}>
          <span>{systemName}</span>

          {gameCount > 1 && (
            <p className="text-neutral-400">
              {'– '}
              {t('{{val, number}} games', {
                gameCount,
                val: gameCount,
              })}
            </p>
          )}
        </div>
      </BaseTableCell>
    </BaseTableRow>
  );
};
