import type { IconType } from 'react-icons';

export interface TicketListFilterPropertyOption {
  label: string;
  value: string;

  count?: number;
  glyph?: { Icon: IconType; className: string };
}
