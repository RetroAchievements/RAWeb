import type { AppPage } from "@/common/models";
import { useTranslation } from "react-i18next";
import { AppLayout } from "@/common/layouts/AppLayout";
import { PatreonSupportersRoot } from "@/features/patreon-supporters/components/+root";

type AuthorizeProps = {
  user: App.Data.User;
  scopes: string[];
  request: {
    scope: string;
    client_id: string;
    redirect_uri: string;
    response_type: string;
  };
  authToken: string;
  client: {
    id: string;
    name: string;
    redirect: string;
    revoked: boolean;
    created_at: string;
    updated_at: string;
    password_client: boolean;
    personal_access_client: boolean;
  };
};
const Authorize: AppPage<AuthorizeProps> = (props) => {
  const { t } = useTranslation();

  return (
    <>
      <div className="container">
        <AppLayout.Main className="min-h-[4000px]">
          <PatreonSupportersRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

Authorize.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Authorize;
