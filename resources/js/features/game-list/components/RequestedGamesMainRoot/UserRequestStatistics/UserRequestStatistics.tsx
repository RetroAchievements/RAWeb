import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

interface UserRequestStatisticsProps {
  targetUser: App.Data.User;
  userRequestInfo: App.Platform.Data.UserSetRequestInfo;
}

export const UserRequestStatistics: FC<UserRequestStatisticsProps> = ({
  targetUser,
  userRequestInfo,
}) => {
  const { auth } = usePageProps();
  const { t } = useTranslation();

  const isOwnRequests = auth?.user?.displayName === targetUser.displayName;

  return (
    <div className="rounded bg-embed p-4">
      <div className="flex flex-col gap-1">
        <p>
          {t('{{used, number}} of {{total, number}} requests made', {
            used: userRequestInfo.used,
            total: userRequestInfo.total,
          })}
        </p>

        {isOwnRequests && userRequestInfo.pointsForNext > 0 ? (
          <p>
            {t('{{points, number}} points until you earn another request', {
              points: userRequestInfo.pointsForNext,
            })}
          </p>
        ) : null}
      </div>
    </div>
  );
};
