import type { FC, ReactNode } from 'react';

interface EmptyStateProps {
  children: ReactNode;
}

export const EmptyState: FC<EmptyStateProps> = ({ children }) => {
  return (
    <div className="flex h-full w-full flex-col items-center justify-center gap-y-2 rounded py-8">
      <img
        src="/assets/images/cheevo/confused.webp"
        alt="empty state"
        className="h-32 w-32"
        width={128}
        height={128}
      />

      <p className="text-balance text-center">{children}</p>
    </div>
  );
};
