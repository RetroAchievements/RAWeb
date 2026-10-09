import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { TicketLogicVersionLink } from '../TicketLogicVersionLink';

interface LogicChangedFactProps {
  achievementId: number;
  currentTriggerVersion: number;
  reportedTriggerVersion: number;
}

export const TicketLogicChangedFact: FC<LogicChangedFactProps> = ({
  achievementId,
  currentTriggerVersion,
  reportedTriggerVersion,
}) => {
  return (
    <Trans
      i18nKey="The logic changed from <1>v{{reportedVersion}}</1> to <2>v{{currentVersion}}</2> after the report."
      values={{ reportedVersion: reportedTriggerVersion, currentVersion: currentTriggerVersion }}
      components={{
        1: (
          <TicketLogicVersionLink achievementId={achievementId} version={reportedTriggerVersion} />
        ),
        2: <TicketLogicVersionLink achievementId={achievementId} version={currentTriggerVersion} />,
      }}
    />
  );
};
