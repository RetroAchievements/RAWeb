import { type FC, type ReactNode, useId } from 'react';

import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import { BaseRadioGroup, BaseRadioGroupItem } from '@/common/components/+vendor/BaseRadioGroup';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

interface TicketCloseQuestionProps {
  label: TranslatedString;
  onValueChange: (value: string) => void;
  options: Array<{ label: TranslatedString; value: string }>;
  value: string | null;

  children?: ReactNode;
}

export const TicketCloseQuestion: FC<TicketCloseQuestionProps> = ({
  children,
  label,
  onValueChange,
  options,
  value,
}) => {
  const controlIdPrefix = useId();
  const questionLabelElementId = `${controlIdPrefix}-question`;

  return (
    <div className="flex flex-col gap-3">
      <p id={questionLabelElementId} className="font-medium text-menu-link">
        {label}
      </p>

      <BaseRadioGroup
        aria-labelledby={questionLabelElementId}
        value={value ?? ''}
        onValueChange={onValueChange}
        className="-mx-3 gap-1"
      >
        {options.map((option) => (
          <BaseLabel
            key={option.value}
            htmlFor={`${controlIdPrefix}-${option.value}`}
            className={cn(
              'flex min-h-10 cursor-pointer items-center gap-3 rounded-md border border-transparent px-3 py-2 leading-snug font-normal',
              'hover:bg-neutral-800 has-data-[state=checked]:border-neutral-700 has-data-[state=checked]:bg-neutral-800 has-data-[state=checked]:text-menu-link',
              'light:hover:bg-neutral-100 light:has-data-[state=checked]:border-neutral-300 light:has-data-[state=checked]:bg-neutral-100',
            )}
          >
            <BaseRadioGroupItem id={`${controlIdPrefix}-${option.value}`} value={option.value} />
            {option.label}
          </BaseLabel>
        ))}
      </BaseRadioGroup>

      {children}
    </div>
  );
};
