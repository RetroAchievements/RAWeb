import type { FC } from 'react';

type CounterProps = {
  icon: string;
  text: string;
  numItems: number;
  numHidden?: number;
};

/*
function RenderCounter(string $icon, string $text, int $numItems, int $numHidden): string
{
    $tooltip = "$numItems $text";
    if ($numHidden > 0) {
        $tooltip .= " ($numHidden hidden)";
    }
    $counter =
        "<div class='cursor-help flex gap-x-1 text-sm' title='$tooltip'>
            <div class='text-2xs'>$icon</div><div class='numitems'>$numItems</div>
        </div>";

    return $counter;
}
 */

export const UserAwardCounter: FC<CounterProps> = ({ icon, text, numItems, numHidden = 0 }) => {
  let tooltip = `${numItems} ${text}`;
  if (numHidden > 0) {
    tooltip += ' ($numHidden hidden)';
  }

  return (
    <div className="flex cursor-help gap-x-1 text-sm" title={tooltip}>
      <div className="text-2xs">{icon}</div>
      <div className="numitems">{numItems}</div>
    </div>
  );
};
