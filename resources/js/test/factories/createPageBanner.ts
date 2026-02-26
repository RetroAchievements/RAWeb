import { createFactory } from '../createFactory';

export const createPageBanner = createFactory<App.Platform.Data.PageBanner>((faker) => {
  return {
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

    leftEdgeColor: '#000000',
    rightEdgeColor: '#ffffff',

    isFallback: false,
  };
});
