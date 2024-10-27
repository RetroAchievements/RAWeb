import type { FC } from 'react';

import { Trans } from '../Trans';

export const SignInMessage: FC = () => {
  return (
    <div className="mt-4 text-center">
      <p>
        <Trans i18nKey="You must <0>sign in</0> before you can join this conversation.">
          {/* eslint-disable react/jsx-no-literals */}
          You must <a href={route('login')}>sign in</a> before you can join this conversation.
          {/* eslint-enable react/jsx-no-literals */}
        </Trans>
      </p>
    </div>
  );
};
