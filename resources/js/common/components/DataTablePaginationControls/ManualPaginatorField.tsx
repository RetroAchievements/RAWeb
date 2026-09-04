import type { ChangeEvent, FC } from 'react';
import { useEffect, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { useDebounce } from 'react-use';

import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { cn } from '@/common/utils/cn';

interface ManualPaginatorFieldProps {
  currentPage: number;
  onPageChange: (pageNumber: number) => void;
  totalPages: number;
}

export const ManualPaginatorField: FC<ManualPaginatorFieldProps> = ({
  currentPage,
  onPageChange,
  totalPages,
}) => {
  const { t } = useTranslation();

  const [inputValue, setInputValue] = useState(String(currentPage));

  useEffect(() => {
    setInputValue(String(currentPage));
  }, [currentPage]);

  // Prevent partial input from overzealously fetching.
  useDebounce(
    () => {
      const typedPage = Number(inputValue);
      if (typedPage >= 1 && typedPage <= totalPages && typedPage !== currentPage) {
        onPageChange(typedPage);
      }
    },
    800,
    [inputValue],
  );

  return (
    <div className="flex items-center gap-2 whitespace-nowrap text-neutral-200 light:text-neutral-900">
      {totalPages === 1 ? (
        <p>
          {t('Page {{currentPage, number}} of {{totalPages, number}}', {
            currentPage: 1,
            totalPages: 1,
          })}
        </p>
      ) : (
        <Trans
          i18nKey="Page <1></1> of {{totalPages, number}}"
          values={{ totalPages }}
          components={{
            1: (
              <BaseInput
                type="number"
                min={1}
                max={totalPages}
                className={cn(
                  'h-8 max-w-20 pt-1.25 text-[13px] text-neutral-200 light:text-neutral-900',

                  // Hide the number spinner on desktop browsers. It can obstruct the input field.
                  'appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none',
                )}
                value={inputValue}
                onChange={(event: ChangeEvent<HTMLInputElement>) =>
                  setInputValue(event.target.value)
                }
                aria-label={t('current page number')}
              />
            ),
          }}
        />
      )}
    </div>
  );
};
