import { useAtom } from 'jotai';
import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';
import { persistedUsersAtom } from '@/features/forums/state/forum.atoms';

interface ShortcodeUserProps {
  displayName: string;
}

export const ShortcodeUser: FC<ShortcodeUserProps> = ({ displayName }) => {
  const [persistedUsers] = useAtom(persistedUsersAtom);

  const foundUser = persistedUsers?.find((user) => user.displayName === displayName);

  if (!foundUser) {
    return null;
  }

  return (
    <span data-testid="user-embed" className="inline-block">
      <UserAvatar {...foundUser} showImage={false} />
    </span>
  );
};
