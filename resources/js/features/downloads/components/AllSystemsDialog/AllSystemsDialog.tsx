import { useAtom, useSetAtom } from 'jotai';
import { type ChangeEvent, type FC, useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { RxMagnifyingGlass } from 'react-icons/rx';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { isAllSystemsDialogOpenAtom, selectedSystemIdAtom } from '../../state/downloads.atoms';

export const AllSystemsDialog: FC = () => {
  const { allSystems } = usePageProps<App.Http.Data.DownloadsPageProps>();
  const { t } = useTranslation();

  const [isAllSystemsDialogOpen, setIsAllSystemsDialogOpen] = useAtom(isAllSystemsDialogOpenAtom);
  const setSelectedSystemId = useSetAtom(selectedSystemIdAtom);

  const [searchQuery, setSearchQuery] = useState(''); // State for search input.

  const sortedSystems = allSystems.sort((a, b) => a.name.localeCompare(b.name));

  // Filter systems based on search query matching id, name, or nameShort.
  const filteredSystems = useMemo(() => {
    const query = searchQuery.toLowerCase().trim();
    if (!query) return sortedSystems;

    return sortedSystems.filter((system) => {
      const searchableFields = [
        system.id.toString(),
        system.name.toLowerCase(),
        system.nameShort!.toLowerCase(),
      ];

      return searchableFields.some((field) => field.includes(query));
    });
  }, [sortedSystems, searchQuery]);

  const handleSystemSelect = (systemId?: number) => {
    setSelectedSystemId(systemId);
    setIsAllSystemsDialogOpen(false);
  };

  const handleSearchChange = (event: ChangeEvent<HTMLInputElement>) => {
    setSearchQuery(event.target.value);
  };

  return (
    <BaseDialog open={isAllSystemsDialogOpen} onOpenChange={setIsAllSystemsDialogOpen}>
      <BaseDialogContent className="max-w-2xl">
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Select a Gaming System')}</BaseDialogTitle>
          <BaseDialogDescription className="sr-only">
            {t('Select a Gaming System')}
          </BaseDialogDescription>
        </BaseDialogHeader>

        <div className=":not-first:mt-2">
          <div className="relative">
            <BaseInput
              className="peer ps-9"
              placeholder={t('search for a gaming system...')}
              value={searchQuery}
              onChange={handleSearchChange}
            />
            <div className="pointer-events-none absolute inset-y-0 start-0 flex items-center justify-center ps-3 text-neutral-300">
              <RxMagnifyingGlass className="size-5" />
            </div>
          </div>
        </div>

        <div className="rounded-lg bg-neutral-950 p-4 light:bg-white">
          <div className="grid h-[50vh] grid-cols-2 place-content-start gap-2 overflow-auto sm:grid-cols-4">
            {filteredSystems.map((system) => (
              <button
                key={`all-systems-${system.id}`}
                className={cn(
                  baseButtonVariants({
                    className: cn(
                      'flex flex-col items-center justify-center gap-1 border border-neutral-800 bg-neutral-950',
                      'text-balance rounded-lg px-2 py-3 text-center tracking-tight !transition',
                      'h-20 text-xs text-link hover:bg-neutral-900 hover:text-link-hover',
                    ),
                  }),
                )}
                onClick={() => handleSystemSelect(system.id)}
              >
                <img src={system.iconUrl} width={30} height={30} />
                {system.name.length >= 22 ? system.nameShort : system.name}
              </button>
            ))}
          </div>
        </div>
      </BaseDialogContent>
    </BaseDialog>
  );
};
