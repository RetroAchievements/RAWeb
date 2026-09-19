import { type FC, type FocusEvent, type KeyboardEvent, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCornerDownLeft } from 'react-icons/lu';

import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

interface TicketListFilterTextInputProps {
  initialValue: string;
  label: string;
  onSubmit: (value: string) => void;

  placeholder?: TranslatedString;
}

export const TicketListFilterTextInput: FC<TicketListFilterTextInputProps> = ({
  initialValue,
  label,
  onSubmit,
  placeholder,
}) => {
  const { t } = useTranslation();

  const [draftValue, setDraftValue] = useState(initialValue);

  const inputElRef = useRef<HTMLInputElement>(null);

  // The parent menu traps the Tab key, preventing keyboard accessibility. Override it.
  useEffect(() => {
    inputElRef.current?.focus();
  }, []);

  // Pass focus to the input field rather than deferring to the submenu.
  const handleBlur = (event: FocusEvent<HTMLInputElement>) => {
    const inputEl = event.currentTarget;
    const triggerId = inputEl.closest('[role="menu"]')?.getAttribute('aria-labelledby');

    if (triggerId && event.relatedTarget?.id === triggerId) {
      queueMicrotask(() => inputEl.focus());
    }
  };

  const stopMenuKeyHandling = (event: KeyboardEvent) => event.stopPropagation();

  return (
    <form
      className="relative p-2"
      onSubmit={(event) => {
        event.preventDefault();
        onSubmit(draftValue.trim());
      }}
    >
      <BaseInput
        ref={inputElRef}
        aria-label={t('{{label}} contains', { label })}
        className="h-8 pr-10"
        enterKeyHint="search"
        maxLength={96}
        placeholder={placeholder}
        value={draftValue}
        onBlur={handleBlur}
        onChange={(event) => setDraftValue(event.target.value)}
        onKeyDown={stopMenuKeyHandling}
      />

      <kbd
        aria-hidden="true"
        className={cn(
          'pointer-events-none absolute top-1/2 right-3.5 -translate-y-1/2',
          'inline-flex size-5 items-center justify-center rounded-md border border-neutral-600 bg-neutral-800',
          'text-neutral-200 light:border-neutral-300 light:bg-neutral-200 light:text-neutral-800',
        )}
      >
        <LuCornerDownLeft className="size-3.5" />
      </kbd>
    </form>
  );
};
