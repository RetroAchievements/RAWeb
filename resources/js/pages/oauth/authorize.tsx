import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AuthorizeRoot } from '@/features/auth/components/+authorize';

const Authorize: AppPage<App.Data.OAuthAuthorizePageProps> = () => {
  return <AuthorizeRoot variant="app" />;
};

Authorize.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Authorize;
