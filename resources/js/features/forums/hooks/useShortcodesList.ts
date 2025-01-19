import { useTranslation } from 'react-i18next';
import { BsImageFill } from 'react-icons/bs';
import { FaGamepad, FaTicketAlt, FaUser } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import {
  LuBold,
  LuCode2,
  LuEyeOff,
  LuItalic,
  LuLink,
  LuNetwork,
  LuStrikethrough,
  LuUnderline,
} from 'react-icons/lu';

import type { Shortcode } from '../models';

export function useShortcodesList() {
  const { t } = useTranslation();

  const shortcodesList: Shortcode[] = [
    { icon: LuBold, t_label: t('Bold'), start: '[b]', end: '[/b]' },
    { icon: LuItalic, t_label: t('Italic'), start: '[i]', end: '[/i]' },
    { icon: LuUnderline, t_label: t('Underline'), start: '[u]', end: '[/u]' },
    { icon: LuStrikethrough, t_label: t('Strikethrough'), start: '[s]', end: '[/s]' },
    { icon: LuCode2, t_label: t('Code'), start: '[code]', end: '[/code]' },
    { icon: LuEyeOff, t_label: t('Spoiler'), start: '[spoiler]', end: '[/spoiler]' },
    { icon: BsImageFill, t_label: t('Image'), start: '[img=', end: ']' },
    { icon: LuLink, t_label: t('Link'), start: '[url=', end: ']' },
    { icon: ImTrophy, t_label: t('Achievement'), start: '[ach=', end: ']' },
    { icon: FaGamepad, t_label: t('Game'), start: '[game=', end: ']' },
    { icon: LuNetwork, t_label: t('Hub'), start: '[hub=', end: ']' },
    { icon: FaUser, t_label: t('User'), start: '[user=', end: ']' },
    { icon: FaTicketAlt, t_label: t('Ticket'), start: '[ticket=', end: ']' },
  ];

  return { shortcodesList };
}
