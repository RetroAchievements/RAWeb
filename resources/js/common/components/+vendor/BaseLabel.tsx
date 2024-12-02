/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import * as LabelPrimitive from '@radix-ui/react-label';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/common/utils/cn';

const baseLabelVariants = cva(
  'font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
);

const BaseLabel = React.forwardRef<
  React.ElementRef<typeof LabelPrimitive.Root>,
  React.ComponentPropsWithoutRef<typeof LabelPrimitive.Root> &
    VariantProps<typeof baseLabelVariants>
>(({ className, ...props }, ref) => (
  <LabelPrimitive.Root ref={ref} className={cn(baseLabelVariants(), className)} {...props} />
));
BaseLabel.displayName = 'BaseLabel';

export { BaseLabel };
