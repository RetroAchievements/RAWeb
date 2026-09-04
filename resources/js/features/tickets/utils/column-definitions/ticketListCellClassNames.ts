export const ticketListCellClassNames: Record<string, string> = {
  dimText: 'text-neutral-400 light:text-neutral-600',

  truncate: 'min-w-0 truncate',

  gameResponsive: 'max-lg:hidden',
  userResponsive: 'max-md:hidden',

  entityLinkWrapper: 'group/entity relative z-10 no-underline!',
  entityLinkLabel: 'text-neutral-400 light:text-neutral-600 group-hover/entity:text-link',
} as const;
