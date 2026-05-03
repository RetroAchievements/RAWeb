import { type FC, type ReactNode } from 'react';

import { BaseCheckbox } from '@/common/components/+vendor/BaseCheckbox';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import { cn } from '@/common/utils/cn';

interface DialogCheckboxConfirmationProps {
  checked: boolean;
  children: ReactNode;
  onCheckedChange: (checked: boolean) => void;

  className?: string;
}

export const DialogCheckboxConfirmation: FC<DialogCheckboxConfirmationProps> = ({
  checked,
  children,
  onCheckedChange,
  className,
}) => {
  return (
    <BaseLabel
      className={cn(
        'flex cursor-pointer items-start gap-3 rounded-md border border-neutral-800 bg-neutral-950/70',
        'p-3 text-left text-sm leading-6 light:bg-white',
        className,
      )}
    >
      <BaseCheckbox
        className="mt-1"
        checked={checked}
        onCheckedChange={(nextValue) => onCheckedChange(!!nextValue)}
      />

      {children}
    </BaseLabel>
  );
};
