import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { InertiaLink as Link } from '@/common/components/InertiaLink';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';

const AuthorizeDevice: AppPage<App.Data.AuthorizeDevicePageProps> = (props) => {
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
              href={route('passport.device.authorizations.approve')}
              method="post"
              data={{
                state: props.request.state,
                client_id: props.client.id,
                auth_token: props.authToken,
              }}
            >
              {'Approve'}
            </Link>
            <Link
              className={baseButtonVariants({
                size: 'sm',
                className: 'gap-1',
              })}
              href={route('passport.device.authorizations.deny')}
              method="delete"
              data={{
                state: props.request.state,
                client_id: props.client.id,
                auth_token: props.authToken,
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

AuthorizeDevice.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AuthorizeDevice;
