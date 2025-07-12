import type { FC } from 'react';
import { Trans } from 'react-i18next';
import { route } from 'ziggy-js';

export const SignInMessage: FC = () => {
  return (
    <div className="mt-4 text-center">
      <p>
        <Trans
          i18nKey="You must <1>sign in</1> before you can join this conversation."
          // eslint-disable-next-line jsx-a11y/anchor-has-content -- this is passed in by the consumer
          components={{ 1: <a href={route('login')} /> }}
        />
      </p>
    </div>
  );
};
