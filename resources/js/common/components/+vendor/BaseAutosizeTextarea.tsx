import * as React from 'react';
import { useImperativeHandle } from 'react';
import { useIsomorphicLayoutEffect } from 'react-use';

import { cn } from '@/common/utils/cn';

interface UseBaseAutosizeTextAreaProps {
  textAreaRef: React.MutableRefObject<HTMLTextAreaElement | null>;
  minHeight?: number;
  maxHeight?: number;
  triggerAutoSize: string;
}

export const useBaseAutosizeTextArea = ({
  textAreaRef,
  triggerAutoSize,
  maxHeight = Number.MAX_SAFE_INTEGER,
  minHeight = 0,
}: UseBaseAutosizeTextAreaProps) => {
  const [init, setInit] = React.useState(true);

  // Use useIsomorphicLayoutEffect to prevent layout flickering on client hydration.
  useIsomorphicLayoutEffect(() => {
    const textAreaElement = textAreaRef.current;

    if (textAreaElement) {
      if (init) {
        textAreaElement.style.minHeight = `${minHeight}px`;
        if (maxHeight > minHeight) {
          textAreaElement.style.maxHeight = `${maxHeight}px`;
        }
        setInit(false);
      }

      const scrollPos = window.scrollY;

      textAreaElement.style.height = `${minHeight}px`;
      const scrollHeight = textAreaElement.scrollHeight;

      if (scrollHeight > maxHeight) {
        textAreaElement.style.height = `${maxHeight}px`;
      } else {
        textAreaElement.style.height = `${scrollHeight}px`;
      }

      // Restore the scroll position to prevent page jumps.
      if (typeof window !== 'undefined') {
        window.scrollTo(0, scrollPos);
      }
    }
  }, [textAreaRef.current, triggerAutoSize, init, maxHeight, minHeight]);
};

export type BaseAutosizeTextAreaRef = {
  textArea: HTMLTextAreaElement;
  maxHeight: number;
  minHeight: number;
  focus: () => void;
};

type BaseAutosizeTextAreaProps = {
  maxHeight?: number;
  minHeight?: number;
} & React.TextareaHTMLAttributes<HTMLTextAreaElement>;

export const BaseAutosizeTextarea = React.forwardRef<
  BaseAutosizeTextAreaRef,
  BaseAutosizeTextAreaProps
>(
  (
    {
      maxHeight = Number.MAX_SAFE_INTEGER,
      minHeight = 52,
      className,
      onChange,
      value,
      style,
      ...props
    }: BaseAutosizeTextAreaProps,
    ref: React.Ref<BaseAutosizeTextAreaRef>,
  ) => {
    const textAreaRef = React.useRef<HTMLTextAreaElement | null>(null);
    const [triggerAutoSize, setTriggerAutoSize] = React.useState('');

    // Set initial height for SSR to prevent layout shift.
    const initialStyle = {
      ...style,
      height: `${minHeight}px`,
      minHeight: `${minHeight}px`,
      ...(maxHeight > minHeight ? { maxHeight: `${maxHeight}px` } : {}),
    };

    useBaseAutosizeTextArea({
      textAreaRef,
      triggerAutoSize,
      maxHeight,
      minHeight,
    });

    useImperativeHandle(ref, () => ({
      textArea: textAreaRef.current as HTMLTextAreaElement,
      focus: () => textAreaRef?.current?.focus(),
      maxHeight,
      minHeight,
    }));

    React.useEffect(() => {
      setTriggerAutoSize(value as string);
    }, [props?.defaultValue, value]);

    return (
      <textarea
        {...props}
        value={value}
        ref={textAreaRef}
        style={initialStyle}
        className={cn(
          'border-input bg-background ring-offset-background placeholder:text-muted-foreground',
          'focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm',
          'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-offset-1',
          'disabled:cursor-not-allowed disabled:opacity-50',
          className,
        )}
        onChange={(e) => {
          setTriggerAutoSize(e.target.value);
          onChange?.(e);
        }}
      />
    );
  },
);
BaseAutosizeTextarea.displayName = 'BaseAutosizeTextarea';
