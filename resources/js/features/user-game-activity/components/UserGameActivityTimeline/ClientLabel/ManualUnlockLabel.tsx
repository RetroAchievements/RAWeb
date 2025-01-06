import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { UserAvatar } from '@/common/components/UserAvatar';

interface ManualUnlockLabelProps {
  unlocker: App.Data.User;
}

export const ManualUnlockLabel: FC<ManualUnlockLabelProps> = ({ unlocker }) => {
  return (
    <div className="flex items-center gap-1.5 text-neutral-300 light:text-neutral-400">
      <div className="mr-1">
        <UserAvatar {...unlocker} showLabel={false} />
      </div>

      <Trans
        i18nKey="<1>{{user}}</1> awarded a Manual Unlock"
        components={{
          1: <UserAvatar {...unlocker} showImage={false} />,
        }}
      />
    </div>
  );
};
