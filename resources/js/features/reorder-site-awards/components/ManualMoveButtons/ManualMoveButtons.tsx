import type { FC } from 'react';
import UserAwardData = App.Community.Data.UserAwardData;
import { useTranslation } from 'react-i18next';

interface ManualMoveButtonsProps {
  award: UserAwardData;
  awardCounter: number;
  moveValue: number;
  upLabel?: string;
  downLabel?: string;
  autoScroll?: boolean;
  orientation?: 'vertical' | 'horizontal';
  isHiddenPreChecked?: boolean;
}

export const ManualMoveButtons: FC<ManualMoveButtonsProps> = ({
  award,
  awardCounter,
  isHiddenPreChecked,
  moveValue,
  autoScroll,
  upLabel,
  downLabel,
  orientation,
}) => {
  const { t } = useTranslation();

  const downValue = moveValue;
  const upValue = moveValue * -1;

  const containerClassNames = orientation === 'vertical' ? 'flex flex-col' : 'flex';

  const rowsPlural = moveValue === 1 ? 'row' : 'rows';
  let upA11yLabel = `Move up ${moveValue} ${rowsPlural}`;
  let downA11yLabel = `Move down ${moveValue} ${rowsPlural}`;

  if (moveValue > 10000) {
    upA11yLabel = 'Move to top';
    downA11yLabel = 'Move to bottom';
  }

  return (
    <div className={containerClassNames}>
      <button
        title={upA11yLabel}
        aria-label={upA11yLabel}
        className="btn py-0.5 text-2xs"
        onClick={() => (award.displayOrder += upValue)}
        disabled={isHiddenPreChecked}
      >
        ↑{upLabel}
      </button>

      <button
        title={downA11yLabel}
        aria-label={downA11yLabel}
        className="btn py-0.5 text-2xs"
        onClick={() => (award.displayOrder += downValue)}
        disabled={isHiddenPreChecked}
      >
        ↓{downLabel}
      </button>
    </div>
  );
};
