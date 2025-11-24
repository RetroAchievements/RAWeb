import { useTranslation } from "react-i18next";

import { AppLayout } from "@/common/layouts/AppLayout";
import type { AppPage } from "@/common/models";

const Authorize: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <div className="container">
        <AppLayout.Main className="min-h-[4000px]">
        </AppLayout.Main>
      </div>
    </>
  );
};

Authorize.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Authorize;
