import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { FaGamepad } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import { LuCalendar, LuMessageSquare, LuNetwork, LuSearch, LuUsers } from 'react-icons/lu';

import type { SearchMode } from '@/common/models';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { allScopes } from '../../utils/allScopes';

interface ScopeSelectorProps {
  onScopeChange: (scope: SearchMode) => void;
  scope: SearchMode;
}

export const ScopeSelector: FC<ScopeSelectorProps> = ({ onScopeChange, scope }) => {
  const { t } = useTranslation();

  const scopeConfig: Record<SearchMode, { label: TranslatedString; icon: ReactNode }> = {
    all: {
      label: t('searchFilterAll'),
      icon: <LuSearch className="size-4" />,
    },
    games: {
      label: t('Games'),
      icon: <FaGamepad className="size-4" />,
    },
    users: {
      label: t('Users'),
      icon: <LuUsers className="size-4" />,
    },
    hubs: {
      label: t('Hubs'),
      icon: <LuNetwork className="size-4" />,
    },
    achievements: {
      label: t('Achievements'),
      icon: <ImTrophy className="size-4" />,
    },
    events: {
      label: t('Events'),
      icon: <LuCalendar className="size-4" />,
    },
    forum_comments: {
      label: t('Forum Posts'),
      icon: <LuMessageSquare className="size-4" />,
    },
    comments: {
      label: t('Comments'),
      icon: <LuMessageSquare className="size-4" />,
    },
  };

  return (
    <div className="flex flex-wrap gap-2">
      {allScopes.map((scopeKey) => {
        const config = scopeConfig[scopeKey];

        return (
          <button
            key={scopeKey}
            aria-pressed={scope === scopeKey}
            onClick={() => onScopeChange(scopeKey)}
            className={cn(
              'flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs transition sm:text-sm',
              scope === scopeKey
                ? 'border-neutral-200 bg-neutral-800 text-neutral-50 light:border-neutral-400 light:bg-neutral-200 light:text-neutral-900'
                : 'border-neutral-700 bg-embed text-neutral-300 hover:bg-neutral-800 light:border-neutral-300 light:bg-neutral-100 light:text-neutral-700',
            )}
          >
            {config.icon}
            {config.label}
          </button>
        );
      })}
    </div>
  );
};
