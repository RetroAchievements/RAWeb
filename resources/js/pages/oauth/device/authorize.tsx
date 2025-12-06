import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AuthorizeRoot } from '@/features/auth/components/+authorize';

const AuthorizeDevice: AppPage<App.Data.AuthorizeDevicePageProps> = () => {
  return <AuthorizeRoot variant="device" />;
};

AuthorizeDevice.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default AuthorizeDevice;
