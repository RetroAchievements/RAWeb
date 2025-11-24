import type { AppPage } from "@/common/models";
import { useTranslation } from "react-i18next";
import { AppLayout } from "@/common/layouts/AppLayout";
import { Link } from "@inertiajs/react";
import { route } from "ziggy-js";
import { baseButtonVariants } from "@/common/components/+vendor/BaseButton";

type AuthorizeDeviceProps = {
  user: App.Data.User;
  scopes: string[];
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
  request: {
    state?: string;
    user_code: string;
  };
  authToken: string;
};

const AuthorizeDevice: AppPage<AuthorizeDeviceProps> = (props) => {
  console.log(props);
  return (
    <>
      <div className="container">
        <AppLayout.Main className="min-h-[4000px]">
          <div className="gap-4 flex ">
            <Link
              className={baseButtonVariants({
                size: "sm",
                className: "gap-1",
              })}
              href={route("passport.device.authorizations.approve")}
              method="post"
              data={{
                state: props.request.state,
                client_id: props.client.id,
                auth_token: props.authToken,
              }}
            >
              Approve
            </Link>
            <Link
              className={baseButtonVariants({
                size: "sm",
                className: "gap-1",
              })}
              href={route("passport.device.authorizations.deny")}
              method="delete"
              data={{
                state: props.request.state,
                client_id: props.client.id,
                auth_token: props.authToken,
              }}
            >
              Reject
            </Link>
          </div>
        </AppLayout.Main>
      </div>
    </>
  );
};

AuthorizeDevice.layout = (page) => (
  <AppLayout withSidebar={false}>{page}</AppLayout>
);

export default AuthorizeDevice;
