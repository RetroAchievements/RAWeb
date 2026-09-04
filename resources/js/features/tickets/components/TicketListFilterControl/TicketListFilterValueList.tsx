import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { HiOutlineCheck } from 'react-icons/hi';

import {
  BaseCommand,
  BaseCommandEmpty,
  BaseCommandGroup,
  BaseCommandInput,
  BaseCommandItem,
  BaseCommandList,
} from '@/common/components/+vendor/BaseCommand';
import { BaseDropdownMenuItem } from '@/common/components/+vendor/BaseDropdownMenu';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { cn } from '@/common/utils/cn';

import type { TicketListFilterProperty, TicketListFilterPropertyOption } from '../../models';
import { TICKET_STATE_GLYPHS } from '../../utils/ticketStateGlyphs';

interface TicketListFilterValueListProps {
  onSelect: (value: string) => void;
  property: TicketListFilterProperty;
  selectedValue: string;
}

const MAX_OPTIONS_WITHOUT_SEARCH = 8;

export const TicketListFilterValueList: FC<TicketListFilterValueListProps> = ({
  onSelect,
  property,
  selectedValue,
}) => {
  const { t } = useTranslation();

  const hasGlyphSlot = property.options.some((option) => option.glyphState);

  if (property.options.length <= MAX_OPTIONS_WITHOUT_SEARCH) {
    return (
      <>
        {property.options.map((option) => (
          <BaseDropdownMenuItem
            key={option.value}
            onSelect={() => onSelect(option.value)}
            className="gap-0"
          >
            <TicketListFilterValueRow
              hasGlyphSlot={hasGlyphSlot}
              isSelected={option.value === selectedValue}
              option={option}
            />
          </BaseDropdownMenuItem>
        ))}
      </>
    );
  }

  return (
    // Keep the parent menu's typeahead from handling search input.
    <BaseCommand onKeyDown={(event) => event.stopPropagation()}>
      <BaseCommandInput placeholder={t('Search options...')} />

      <BaseCommandList>
        <BaseCommandEmpty>{t('No results found.')}</BaseCommandEmpty>

        <BaseCommandGroup>
          {property.options.map((option) => (
            <BaseCommandItem
              key={option.value}
              onSelect={() => onSelect(option.value)}
              className="items-center"
            >
              <TicketListFilterValueRow
                hasGlyphSlot={hasGlyphSlot}
                isSelected={option.value === selectedValue}
                option={option}
              />
            </BaseCommandItem>
          ))}
        </BaseCommandGroup>
      </BaseCommandList>
    </BaseCommand>
  );
};

interface TicketListFilterValueRowProps {
  hasGlyphSlot: boolean;
  isSelected: boolean;
  option: TicketListFilterPropertyOption;
}

const TicketListFilterValueRow: FC<TicketListFilterValueRowProps> = ({
  hasGlyphSlot,
  isSelected,
  option,
}) => {
  const { formatNumber } = useFormatNumber();

  const glyph = option.glyphState ? TICKET_STATE_GLYPHS[option.glyphState] : null;

  return (
    <span className="flex w-full items-center">
      <span
        className={cn(
          'mr-2 flex size-4 min-w-4 items-center justify-center rounded-full',
          'border border-neutral-600 light:border-neutral-900',
          isSelected
            ? 'border-neutral-50 bg-neutral-700 text-neutral-50 light:bg-text'
            : 'opacity-50',
        )}
      >
        {isSelected ? (
          <HiOutlineCheck
            data-testid={`checked-${option.value}`}
            aria-hidden={true}
            className="size-4"
          />
        ) : null}
      </span>

      {hasGlyphSlot ? (
        <span className="mr-2 flex size-4 min-w-4 items-center justify-center">
          {glyph ? (
            <glyph.Icon
              aria-hidden={true}
              data-testid={`glyph-${option.value}`}
              className={cn('size-4', glyph.className)}
            />
          ) : null}
        </span>
      ) : null}

      <span className="text-neutral-200 light:text-neutral-900">{option.label}</span>

      {option.count !== undefined ? (
        <span className="ml-auto pl-3 text-neutral-400 tabular-nums light:text-neutral-600">
          {formatNumber(option.count)}
        </span>
      ) : null}
    </span>
  );
};
