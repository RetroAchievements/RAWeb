import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuCircleAlert } from 'react-icons/lu';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import { usePageProps } from '@/common/hooks/usePageProps';

interface TemplateKindAlertProps {
  templateKind: App.Community.Enums.MessageThreadTemplateKind;
}

export const TemplateKindAlert: FC<TemplateKindAlertProps> = ({ templateKind }) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  return (
    <BaseAlert>
      <LuCircleAlert className="size-5" />

      <BaseAlertTitle>{t('Important')}</BaseAlertTitle>
      <BaseAlertDescription>
        <div className="leading-5">
          {auth?.user.locale && !auth.user.locale.startsWith('en_') ? (
            <p>
              {t(
                'Please write your message in English. This helps ensure the issue can be resolved as quickly as possible.',
              )}
            </p>
          ) : null}

          {templateKind === 'manual-unlock' ? (
            <>
              <p>
                {t('Please provide as much evidence as possible in your manual unlock request.')}
              </p>
              <p>
                {t(
                  'The person reviewing the request will almost always want either a video or screenshot.',
                )}
              </p>
            </>
          ) : null}

          {templateKind === 'unwelcome-concept' ? (
            <>
              <p>{t("Follow the template below. If you don't, your request will be ignored.")}</p>

              <p>
                <Trans
                  i18nKey="When in doubt, <1>consult the docs.</1>"
                  components={{
                    1: (
                      // eslint-disable-next-line jsx-a11y/anchor-has-content -- this is passed in by the consumer
                      <a
                        href="https://docs.retroachievements.org/guidelines/content/unwelcome-concepts.html"
                        target="_blank"
                      />
                    ),
                  }}
                />
              </p>
            </>
          ) : null}

          {templateKind !== 'manual-unlock' && templateKind !== 'unwelcome-concept' ? (
            <p>{t('Please provide as much information as possible about the issue.')}</p>
          ) : null}
        </div>
      </BaseAlertDescription>
    </BaseAlert>
  );
};
