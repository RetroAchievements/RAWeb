import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { RxMagnifyingGlass } from 'react-icons/rx';

import { BaseCardContent } from '@/common/components/+vendor/BaseCard';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { useSearchInputHotkey } from '@/common/hooks/useSearchInputHotkey';

import { searchQueryAtom } from '../../state/downloads.atoms';

export const SearchEmulators: FC = () => {
  const { t } = useTranslation();

  const [searchQuery, setSearchQuery] = useAtom(searchQueryAtom);

  const { hotkeyInputRef } = useSearchInputHotkey({ isEnabled: true, key: '/' });

  return (
    <div className="hidden sm:block">
      <BaseCardContent className="flex flex-col gap-1.5">
        <p className="font-semibold">{t('Search Emulators')}</p>

        <div className=":not-first:mt-2">
          <div className="relative">
            <div className="pointer-events-none absolute inset-y-0 start-0 flex items-center justify-center ps-3 text-neutral-300">
              <RxMagnifyingGlass className="size-5" />
            </div>

            <BaseInput
              ref={hotkeyInputRef}
              className="peer ps-9"
              placeholder={t('search by name...')}
              value={searchQuery}
              onChange={(event) => setSearchQuery(event.target.value)}
            />

            <div className="pointer-events-none absolute inset-y-0 end-0 hidden items-center justify-center pe-2 sm:flex">
              <kbd className="inline-flex h-5 max-h-full items-center rounded-md border border-embed-highlight bg-neutral-800/60 px-1.5 font-mono text-xs text-neutral-400 light:bg-neutral-200 light:text-neutral-800">
                {'/'}
              </kbd>
            </div>
          </div>
        </div>
      </BaseCardContent>
    </div>
  );
};
