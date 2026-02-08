import type { FC } from 'react';

interface ManualMoveButtonsProps {
  awardCounter: number;
  moveValue: number;
  upLabel?: string;
  downLabel?: string;
  autoScroll?: boolean;
  orientation?: 'vertical' | 'horizontal';
  isHiddenPreChecked?: boolean;
}

export const ManualMoveButtons: FC<ManualMoveButtonsProps> = ({
  awardCounter,
  isHiddenPreChecked,
  moveValue,
  autoScroll,
  upLabel,
  downLabel,
  orientation,
}) => {
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
        onClick="reorderSiteAwards.moveRow($awardCounter, $upValue, $autoScroll)"
        disabled={isHiddenPreChecked}
      >
        ↑{upLabel}
      </button>

      <button
        title={downA11yLabel}
        aria-label={downA11yLabel}
        className="btn py-0.5 text-2xs"
        onClick="reorderSiteAwards.moveRow($awardCounter, $downValue, $autoScroll)"
        disabled={isHiddenPreChecked}
      >
        ↓{downLabel}
      </button>
    </div>
  );
};
