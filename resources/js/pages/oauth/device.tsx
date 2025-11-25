import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { EnterDeviceCodeRoot } from '@/features/auth/components/+enter-device-code';

const EnterDeviceCode: AppPage<App.Data.EnterDeviceCodePageProps> = () => {
  return <EnterDeviceCodeRoot />;
};

EnterDeviceCode.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default EnterDeviceCode;
