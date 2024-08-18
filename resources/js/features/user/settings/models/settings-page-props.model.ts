import { type AppGlobalProps, createAppGlobalProps } from '@/common/models';
import { convertObjectToWebsitePrefs } from '@/common/utils/convertObjectToWebsitePrefs';
import { UserPreference } from '@/common/utils/generatedAppConstants';
import { createFactory } from '@/test/createFactory';

type LazyLoadedUserProps = Pick<
  App.Data.User,
  'apiKey' | 'emailAddress' | 'motto' | 'userWallActive' | 'visibleRole' | 'websitePrefs'
>;
type SettingsPageExtendedUser = Required<LazyLoadedUserProps> &
  Pick<App.Data.User, 'deleteRequested'>;

interface SettingsPagePermissions {
  manipulateApiKeys: boolean;
  updateAvatar: boolean;
  updateMotto: boolean;
}

export interface SettingsPageProps extends AppGlobalProps {
  userSettings: SettingsPageExtendedUser;
  can: SettingsPagePermissions;
}

export const createSettingsPageExtendedUser = createFactory<SettingsPageExtendedUser>((faker) => ({
  apiKey: faker.string.uuid(),
  emailAddress: faker.internet.email(),
  motto: faker.word.words(4),
  userWallActive: faker.datatype.boolean(),
  visibleRole: null,
  websitePrefs: convertObjectToWebsitePrefs({
    [UserPreference.EmailOn_Followed]: true,
  }),
}));

export const createSettingsPagePermissions = createFactory<SettingsPagePermissions>(() => ({
  manipulateApiKeys: true,
  updateAvatar: true,
  updateMotto: true,
}));

export const createSettingsPageProps = createFactory<SettingsPageProps>(() => ({
  ...createAppGlobalProps(),
  can: createSettingsPagePermissions(),
  userSettings: createSettingsPageExtendedUser(),
}));
