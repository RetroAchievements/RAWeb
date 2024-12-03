import { dangerouslyGetClientSideAssetUrl } from './dangerouslyGetClientSideAssetUrl';

describe('Util: dangerouslyGetClientSideAssetUrl', () => {
  beforeEach(() => {
    vi.stubGlobal('window', {
      assetUrl: 'http://localhost:64000',
      cfg: {
        assetsUrl: 'http://localhost:64000/assets',
      },
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('is defined', () => {
    // ASSERT
    expect(dangerouslyGetClientSideAssetUrl).toBeDefined();
  });

  it('given a config value exists, uses that for the base URL', () => {
    // ARRANGE
    const uri = '/images/test.png';

    // ACT
    const result = dangerouslyGetClientSideAssetUrl(uri);

    // ASSERT
    expect(result).toBe('http://localhost:64000/assets/images/test.png');
  });

  it('given no config value exists, falls back to window.assetUrl', () => {
    // ARRANGE
    const uri = '/images/test.png';
    vi.stubGlobal('window', {
      assetUrl: 'http://localhost:3000',
      cfg: {},
    });

    // ACT
    const result = dangerouslyGetClientSideAssetUrl(uri);

    // ASSERT
    expect(result).toBe('http://localhost:3000/images/test.png');
  });
});
