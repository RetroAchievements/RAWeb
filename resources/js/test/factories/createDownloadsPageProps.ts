import { createFactory } from '../createFactory';

export const createDownloadsPageProps = createFactory<App.Http.Data.DownloadsPageProps>(() => {
  return {
    allEmulators: [],
    allPlatforms: [],
    allSystems: [],
    can: {},
    popularEmulatorsBySystem: [],
    topSystemIds: [],
    userDetectedPlatformId: null,
    userSelectedSystemId: null,
  };
});
