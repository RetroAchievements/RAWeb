import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

interface NewsCategoryLabelProps {
  category: App.Community.Enums.NewsCategory;
}

export const NewsCategoryLabel: FC<NewsCategoryLabelProps> = ({ category }) => {
  const { t } = useTranslation();

  return (
    <span>
      {/* eslint-disable-next-line @typescript-eslint/no-explicit-any -- this is intentional */}
      {t(`news-category.${category}` as any)}
    </span>
  );
};
