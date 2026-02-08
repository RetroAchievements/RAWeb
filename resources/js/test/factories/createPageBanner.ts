import { createFactory } from '../createFactory';

export const createPageBanner = createFactory<App.Platform.Data.PageBanner>((faker) => {
  return {
    // media
    desktopLgAvif: faker.internet.url(),
    desktopLgWebp: faker.internet.url(),
    desktopMdAvif: faker.internet.url(),
    desktopMdWebp: faker.internet.url(),
    desktopPlaceholder: faker.internet.url(),
    desktopXlAvif: faker.internet.url(),
    desktopXlWebp: faker.internet.url(),
    mobileMdAvif: faker.internet.url(),
    mobileMdWebp: faker.internet.url(),
    mobilePlaceholder: faker.internet.url(),
    mobileSmAvif: faker.internet.url(),
    mobileSmWebp: faker.internet.url(),

    // custom properties
    leftEdgeColor: '#000000',
    rightEdgeColor: '#ffffff',
  };
});
