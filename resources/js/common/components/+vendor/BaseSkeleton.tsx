import { cn } from '@/common/utils/cn';

function BaseSkeleton({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('animate-pulse rounded-md bg-embed', className)} {...props} />;
}

export { BaseSkeleton };
