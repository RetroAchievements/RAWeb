import type { FC, ReactNode } from 'react';

import type { TranslatedString } from '@/types/i18next';

interface ReportIssueSectionProps {
  children: ReactNode;
  t_heading: TranslatedString;

  t_description?: TranslatedString;
}

export const ReportIssueSection: FC<ReportIssueSectionProps> = ({
  children,
  t_heading,
  t_description,
}) => {
  return (
    <section className="flex flex-col gap-2">
      <h2 className="mb-0 border-b-0 text-lg font-semibold text-neutral-200 light:text-neutral-900">
        {t_heading}
      </h2>

      {t_description ? (
        <p className="text-neutral-300 light:text-neutral-700">{t_description}</p>
      ) : null}

      <ul className="flex flex-col gap-3">{children}</ul>
    </section>
  );
};
