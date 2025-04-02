import { useAtomValue } from 'jotai';
import type { FC } from 'react';

import { persistedUsersAtom } from '../../../state/shortcode.atoms';
import { UserAvatar } from '../../UserAvatar';

interface ShortcodeUserProps {
  displayName: string;
}

export const ShortcodeUser: FC<ShortcodeUserProps> = ({ displayName }) => {
  const persistedUsers = useAtomValue(persistedUsersAtom);

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
