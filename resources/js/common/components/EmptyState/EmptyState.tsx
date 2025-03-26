import type { FC, ReactNode } from 'react';

interface EmptyStateProps {
  children: ReactNode;

  shouldShowImage?: boolean;
}

export const EmptyState: FC<EmptyStateProps> = ({ children, shouldShowImage = true }) => {
  return (
    <div className="flex h-full w-full flex-col items-center justify-center gap-y-2 rounded py-8">
      {shouldShowImage ? (
        <img
          src="/assets/images/cheevo/confused.webp"
          alt="empty state"
          className="h-32 w-32"
          width={128}
          height={128}
        />
      ) : null}

      <p className="text-balance text-center">{children}</p>
    </div>
  );
};
