import type { FC, ReactNode } from 'react';

interface ResultSectionProps {
  title: string;
  icon: ReactNode;
  children: ReactNode;
}

export const ResultSection: FC<ResultSectionProps> = ({ title, icon, children }) => {
  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-center gap-2 text-sm font-medium text-neutral-400 light:text-neutral-600">
        {icon}
        {title}
      </div>

      <div className="flex flex-col gap-1">{children}</div>
    </div>
  );
};
