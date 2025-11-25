import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { InertiaLink as Link } from '@/common/components/InertiaLink';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

const Authorize: AppPage<App.Data.OAuthAuthorizePageProps> = ({ authToken, client, request }) => {
  return (
    <>
      <div className="container">
        <AppLayout.Main className="min-h-[4000px]">
          <div className="flex gap-4">
            <Link
              className={baseButtonVariants({
                size: 'sm',
                className: 'gap-1',
              })}
              href={route('passport.authorizations.approve')}
              method="post"
              data={{
                state: request.state,
                client_id: client.id,
                auth_token: authToken,
              }}
            >
              {'Approve'}
            </Link>
            <Link
              className={baseButtonVariants({
                size: 'sm',
                className: 'gap-1',
              })}
              href={route('passport.authorizations.deny')}
              method="delete"
              data={{
                state: request.state,
                client_id: client.id,
                auth_token: authToken,
              }}
            >
              {'Reject'}
            </Link>
          </div>
        </AppLayout.Main>
      </div>
    </>
  );
};

Authorize.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Authorize;
