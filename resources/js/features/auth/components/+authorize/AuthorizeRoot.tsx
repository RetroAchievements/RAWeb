import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuGamepad2, LuSatelliteDish, LuUser, LuX } from 'react-icons/lu';
import type { RouteName } from 'ziggy-js';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseCard,
  BaseCardContent,
  BaseCardDescription,
  BaseCardFooter,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';
import { usePageProps } from '@/common/hooks/usePageProps';

import { OAuthPageLayout } from '../OAuthPageLayout';

type OAuthVariant = 'app' | 'device';

interface AuthorizeRootProps {
  variant: OAuthVariant;
}

const routesByVariant: Record<OAuthVariant, { approve: RouteName; deny: RouteName }> = {
  app: {
    approve: 'passport.authorizations.approve',
    deny: 'passport.authorizations.deny',
  },
  device: {
    approve: 'passport.device.authorizations.approve',
    deny: 'passport.device.authorizations.deny',
  },
};

export const AuthorizeRoot: FC<AuthorizeRootProps> = ({ variant }) => {
  const { auth, authToken, client, csrfToken, request } =
    usePageProps<App.Data.OAuthAuthorizePageProps>();
  const { t } = useTranslation();

  const routes = routesByVariant[variant];

  return (
    <OAuthPageLayout>
      <BaseCard className="rounded-2xl p-8 shadow-lg shadow-black/20 ring-1 ring-white/5">
        <BaseCardHeader className="text-balance px-0 pt-0 text-center">
          <div className="mb-6 flex justify-center">
            <div className="rounded-xl bg-amber-500/10 p-3">
              <LuGamepad2 className="size-8 text-amber-500 light:text-amber-600" />
            </div>
          </div>

          <BaseCardTitle className="text-base font-semibold leading-normal text-neutral-300 light:text-neutral-900">
            {t('{{clientName}} wants to access your account', { clientName: client.name })}
          </BaseCardTitle>
          <BaseCardDescription className="text-neutral-500 light:text-neutral-700">
            {t('This will allow {{clientName}} to:', {
              clientName: client.name,
              nsSeparator: null,
            })}
          </BaseCardDescription>
        </BaseCardHeader>

        <BaseCardContent className="flex flex-col gap-6 px-0 text-neutral-300 light:text-neutral-900">
          {/* Permissions description */}
          <div className="flex flex-col gap-3 rounded-lg bg-neutral-950 p-4 light:border light:border-neutral-200 light:bg-neutral-100">
            <div className="flex items-center gap-3 text-xs">
              <LuUser className="size-4 min-w-4" />
              <p>{t('Access your profile information')}</p>
            </div>

            <div className="flex items-center gap-3 text-xs">
              <LuSatelliteDish className="size-4 min-w-4" />
              <p>{t('Make API calls on your behalf')}</p>
            </div>
          </div>

          {/* Current user details */}
          <div className="flex items-center gap-3">
            <img
              src={auth?.user.avatarUrl}
              className="size-8 rounded-lg bg-neutral-950 light:bg-neutral-100"
              alt="user avatar"
            />

            <div className="flex flex-col">
              <p className="text-xs text-neutral-400 light:text-neutral-800">
                {t('Currently signed in as')}
              </p>
              <p className="text-neutral-300 light:text-neutral-900">{auth?.user.displayName}</p>
            </div>
          </div>
        </BaseCardContent>

        {/* Use native forms because OAuth redirects to external URLs (the client's redirect_uri). */}
        <BaseCardFooter className="grid grid-cols-2 gap-3 px-0 pb-0 sm:px-6">
          <form method="POST" action={route(routes.deny)}>
            <input type="hidden" name="_token" value={csrfToken} />
            <input type="hidden" name="_method" value="DELETE" />
            <input type="hidden" name="state" value={request.state ?? ''} />
            <input type="hidden" name="client_id" value={client.id} />
            <input type="hidden" name="auth_token" value={authToken} />

            <BaseButton
              type="submit"
              size="lg"
              className="flex w-full items-center gap-1"
              variant="link"
            >
              <LuX className="size-4 min-w-4" />
              {t('Deny')}
            </BaseButton>
          </form>

          <form method="POST" action={route(routes.approve)}>
            <input type="hidden" name="_token" value={csrfToken} />
            <input type="hidden" name="state" value={request.state ?? ''} />
            <input type="hidden" name="client_id" value={client.id} />
            <input type="hidden" name="auth_token" value={authToken} />

            <BaseButton type="submit" size="lg" className="flex w-full items-center gap-1">
              <LuCheck className="size-4 min-w-4" />
              {t('Authorize')}
            </BaseButton>
          </form>
        </BaseCardFooter>
      </BaseCard>
    </OAuthPageLayout>
  );
};
