import type { AppPage } from "@/common/models";
import { useTranslation } from "react-i18next";
import { AppLayout } from "@/common/layouts/AppLayout";
import { route } from "ziggy-js";
import { Link } from "@inertiajs/react";
import { baseButtonVariants } from "@/common/components/+vendor/BaseButton";
import { useState } from "react";

type AuthorizeDeviceProps = {
  request: {
    client_id: string;
  };
};
const DeviceCode: AppPage<AuthorizeDeviceProps> = (props) => {
  const [userCode, setUserCode] = useState("");

  return (
    <>
      <div className="container">
        <AppLayout.Main className="min-h-[4000px]">
          <p>enter your code below</p>
          <input
            type="text"
            placeholder="here plz"
            value={userCode}
            onChange={(e) => setUserCode(e.target.value)}
          />
          <Link
            className={baseButtonVariants({
              size: "sm",
              className: "gap-1",
            })}
            href={route("passport.device.authorizations.authorize")}
            method="get"
            data={{ user_code: userCode }}
          >
            Submit
          </Link>
        </AppLayout.Main>
      </div>
    </>
  );
};

DeviceCode.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default DeviceCode;
