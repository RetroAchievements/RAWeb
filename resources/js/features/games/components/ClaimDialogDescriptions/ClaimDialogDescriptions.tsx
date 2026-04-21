import type { FC, ReactNode } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';
import {
  ForumTopicNotice,
  ProgressReportWarning,
  QuickCompletionWarning,
  RevisionPlanWarning,
  SubsetApprovalWarning,
  UnresolvedTicketsWarning,
} from '@/features/games/components/ClaimDialogWarnings';
import type { ClaimActionType } from '@/features/games/hooks/useClaimActions';
import { useClaimDialogState } from '@/features/games/hooks/useClaimDialogState';
import type { CreateClaimDialogVariant } from '@/features/games/models';

interface ClaimDialogDescriptionsProps {
  action: ClaimActionType;
}

export const ClaimDialogDescriptions: FC<ClaimDialogDescriptionsProps> = ({ action }) => {
  const { backingGame } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const {
    createClaimDialogVariant,
    hasForumTopicNotice,
    hasQuickCompletionWarning,
    hasRevisionPlanNotice,
    hasSubsetApprovalNotice,
    requiresTicketAcknowledgment,
    unresolvedTicketCount,
  } = useClaimDialogState(action);

  const createClaimDescriptionKeys = {
    newSet: 'This will create a primary claim for <1>{{gameTitle}}</1>.',
    revision: 'This will create a revision claim for <1>{{gameTitle}}</1>.',
    collaboration: 'This will create a collaboration claim for <1>{{gameTitle}}</1>.',
  } as const satisfies Record<CreateClaimDialogVariant, Parameters<typeof t>[0]>;

  switch (action) {
    case 'create': {
      const notices: ReactNode[] = [];

      if (hasRevisionPlanNotice) {
        notices.push(<RevisionPlanWarning key="revision-plan" />);
      }

      if (hasSubsetApprovalNotice) {
        notices.push(<SubsetApprovalWarning key="subset-approval" />);
      }

      if (requiresTicketAcknowledgment) {
        notices.push(
          <UnresolvedTicketsWarning key="unresolved-tickets" ticketCount={unresolvedTicketCount} />,
        );
      }

      if (hasForumTopicNotice) {
        notices.push(<ForumTopicNotice key="forum-topic" />);
      }

      return (
        <div className="flex flex-col gap-4">
          <p className="text-sm leading-6 text-text">
            <Trans
              i18nKey={createClaimDescriptionKeys[createClaimDialogVariant]}
              components={{
                1: <GameTitle title={backingGame.title} />,
              }}
            />
          </p>

          <ClaimDialogNoticeBlock items={notices} />
        </div>
      );
    }

    case 'drop':
      return (
        <p className="text-sm leading-6 text-text">
          <Trans
            i18nKey="This will drop your current claim for <1>{{gameTitle}}</1>."
            components={{
              1: <GameTitle title={backingGame.title} />,
            }}
          />
        </p>
      );

    case 'extend': {
      const notices: ReactNode[] = [<ProgressReportWarning key="progress-report" />];

      return (
        <div className="flex flex-col gap-4">
          <p className="text-sm leading-6 text-text">
            {t('This will extend your claim for another three months.')}
          </p>

          <ClaimDialogNoticeBlock items={notices} />
        </div>
      );
    }

    case 'complete': {
      const notices: ReactNode[] = [];

      if (hasQuickCompletionWarning) {
        notices.push(<QuickCompletionWarning key="quick-completion" />);
      }

      return (
        <div className="flex flex-col gap-4">
          <p className="text-sm leading-6 text-text">
            {t(
              'This will mark your claim complete and notify set requestors that new achievements have been added.',
            )}
          </p>

          <ClaimDialogNoticeBlock items={notices} />
        </div>
      );
    }

    default:
      return null;
  }
};

interface ClaimDialogNoticeBlockProps {
  items: ReactNode[];
}

const ClaimDialogNoticeBlock: FC<ClaimDialogNoticeBlockProps> = ({ items }) => {
  const { t } = useTranslation();

  if (items.length === 0) {
    return null;
  }

  return (
    <div
      role="alert"
      className="rounded-md border border-neutral-800 bg-embed px-4 py-3 text-text light:border-neutral-200 light:bg-white"
    >
      <p className="text-sm font-medium text-neutral-100 light:text-neutral-900">
        {t('Before you continue')}
      </p>

      <div className="mt-3 space-y-2 text-sm leading-6">
        {items.map((item, index) => (
          <p key={index}>{item}</p>
        ))}
      </div>
    </div>
  );
};
