import type { FC, ReactNode } from 'react';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { cn } from '@/utils/cn';

interface ReportIssueOptionItemProps {
  buttonText: string;
  children: ReactNode;
  href: string;

  anchorClassName?: string;
}

export const ReportIssueOptionItem: FC<ReportIssueOptionItemProps> = ({
  buttonText,
  children,
  href,
  anchorClassName,
}) => {
  return (
    <li className="flex w-full flex-col items-center justify-between gap-2 rounded bg-embed px-3 py-2 sm:flex-row">
      <p>{children}</p>

      <div className="self-end sm:self-auto">
        <a href={href} className={cn(baseButtonVariants({ size: 'sm' }), anchorClassName)}>
          {buttonText}
        </a>
      </div>
    </li>
  );
};
