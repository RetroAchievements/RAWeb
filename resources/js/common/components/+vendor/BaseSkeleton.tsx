import { useLaravelReactI18n } from 'laravel-react-i18n';

import { cn } from '@/utils/cn';

function BaseSkeleton({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  const { t } = useLaravelReactI18n();

  return (
    <div
      role="status"
      aria-busy={true}
      aria-label={t('Loading...')}
      className={cn('animate-pulse rounded-md bg-embed', className)}
      {...props}
    />
  );
}

export { BaseSkeleton };
