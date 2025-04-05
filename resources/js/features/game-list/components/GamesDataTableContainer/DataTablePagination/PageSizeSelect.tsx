import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';

interface PageSizeSelectProps {
  onChange: (newPageSize: number) => void;
  onMouseEnterPageSizeOption: (pageSize: number) => void;
  value: number;
}

export const PageSizeSelect: FC<PageSizeSelectProps> = ({
  onChange,
  onMouseEnterPageSizeOption,
  value,
}) => {
  const { t } = useTranslation();

  const availablePageSizes: number[] = [10, 25, 50, 100, 200];

  return (
    <div className="flex items-center gap-2">
      <label id="rows-per-page-label" htmlFor="rows-per-page-select">
        {t('Rows per page')}
      </label>

      <BaseSelect
        value={`${value}`}
        onValueChange={(value) => {
          onChange(Number(value));
        }}
      >
        <BaseSelectTrigger
          id="rows-per-page-select"
          aria-labelledby="rows-per-page-label"
          className="h-8 w-[70px]"
        >
          <BaseSelectValue placeholder={value} />
        </BaseSelectTrigger>

        <BaseSelectContent side="top">
          {availablePageSizes.map((pageSize) => (
            <BaseSelectItem
              key={pageSize}
              value={`${pageSize}`}
              onMouseEnter={() => {
                onMouseEnterPageSizeOption(pageSize);
              }}
            >
              {pageSize}
            </BaseSelectItem>
          ))}
        </BaseSelectContent>
      </BaseSelect>
    </div>
  );
};
