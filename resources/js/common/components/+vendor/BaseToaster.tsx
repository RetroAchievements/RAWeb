/* eslint-disable no-restricted-imports -- base components can import from sonner */

import type { ComponentProps } from 'react';
import { toast, Toaster as Sonner } from 'sonner';

import { cn } from '@/common/utils/cn';

type BaseToasterProps = ComponentProps<typeof Sonner>;

const BaseToaster = ({ ...props }: BaseToasterProps) => {
  const theme = 'dark';

  return (
    <Sonner
      theme={theme as BaseToasterProps['theme']}
      className="toaster group"
      toastOptions={{
        classNames: {
          toast: cn(
            'group toast transition-all duration-300',
            'group-[.toaster]:bg-neutral-950 group-[.toaster]:shadow-lg group-[.toaster]:text-neutral-50 group-[.toaster]:border-neutral-800',
            'light:group-[.toaster]:bg-white light:group-[.toaster]:text-neutral-950 light:group-[.toaster]:border-neutral-200',
          ),

          error: '!bg-red-800 !text-red-100 light:!bg-red-600',
          success: '!bg-green-800 !text-green-100 light:!bg-green-600',
          info: '!bg-blue-800 !text-blue-100 light:!bg-blue-600',
          warning: '!bg-yellow-800 !text-yellow-100 light:!bg-yellow-600',

          description: 'light:group-[.toast]:text-neutral-500 group-[.toast]:text-neutral-400',

          actionButton:
            'light:group-[.toast]:bg-neutral-900 light:group-[.toast]:text-neutral-50 group-[.toast]:bg-neutral-50 group-[.toast]:text-neutral-900',

          cancelButton:
            'light:group-[.toast]:bg-neutral-100 light:group-[.toast]:text-neutral-500 group-[.toast]:bg-neutral-800 group-[.toast]:text-neutral-400',
        },
      }}
      {...props}
    />
  );
};

// Rename toast, otherwise IDEs will always try to auto-import from sonner instead of our own.
const toastMessage = toast;

export { BaseToaster, toastMessage };
