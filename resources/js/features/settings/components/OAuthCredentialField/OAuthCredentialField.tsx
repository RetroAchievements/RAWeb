import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCopy } from 'react-icons/lu';
import { useCopyToClipboard } from 'react-use';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';

import { safeFormatCredential } from '../../utils/safeFormatCredential';

interface OAuthCredentialFieldProps {
  credentialName: string;
  label: string;
  value: string;
}

export const OAuthCredentialField: FC<OAuthCredentialFieldProps> = ({
  credentialName,
  label,
  value,
}) => {
  const { t } = useTranslation();

  const [, copyToClipboard] = useCopyToClipboard();

  const handleCopy = () => {
    copyToClipboard(value);
    toastMessage.success(t('Copied!'));
  };

  return (
    <div className="flex flex-col gap-2 sm:grid sm:grid-cols-[7rem_minmax(0,1fr)] sm:items-center">
      <p className="text-menu-link">{label}</p>
      <BaseButton
        aria-label={t('Copy {{credential}}', { credential: credentialName })}
        className="flex h-auto min-w-0 items-center justify-center gap-2 px-3 py-2"
        type="button"
        onClick={handleCopy}
      >
        <LuCopy className="shrink-0" aria-hidden="true" />
        <span className="min-w-0 truncate text-center font-mono">
          {safeFormatCredential(value)}
        </span>
      </BaseButton>
    </div>
  );
};
