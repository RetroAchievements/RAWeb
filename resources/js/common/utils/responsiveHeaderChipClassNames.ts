import { cn } from '@/common/utils/cn';

export const responsiveHeaderChipClassNames = cn(
  'flex max-w-fit items-center rounded-full',
  'border bg-black/70 shadow-md backdrop-blur-sm',
  'gap-1 border-white/30 px-2.5 py-1',

  'sm:gap-1.5 sm:border-white/20 sm:px-3 sm:py-1.5',
  'sm:hover:border-link-hover sm:hover:bg-black/80',

  'light:border-neutral-300 light:bg-white/80 light:backdrop-blur-md',
  'light:sm:hover:bg-white/90',
);
