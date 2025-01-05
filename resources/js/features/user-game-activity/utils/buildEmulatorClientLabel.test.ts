import { createParsedUserAgent } from '@/test/factories';

import { buildEmulatorClientLabel } from './buildEmulatorClientLabel';

describe('Util: buildEmulatorClientLabel', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildEmulatorClientLabel).toBeDefined();
  });

  it('given no user agent data is provided, returns an empty string', () => {
    // ACT
    const result = buildEmulatorClientLabel(undefined);

    // ASSERT
    expect(result).toEqual('');
  });

  it('given only client and version are provided, returns the basic label format', () => {
    // ACT
    const result = buildEmulatorClientLabel(
      createParsedUserAgent({
        client: 'RALibRetro',
        clientVersion: '1.19.1',
      }),
    );

    // ASSERT
    expect(result).toEqual('RALibRetro 1.19.1');
  });

  it('given a variation is provided, includes it in the label', () => {
    // ACT
    const result = buildEmulatorClientLabel(
      createParsedUserAgent({
        client: 'RALibRetro',
        clientVersion: '1.19.1',
        clientVariation: 'mesen',
      }),
    );

    // ASSERT
    expect(result).toEqual('RALibRetro 1.19.1 - mesen');
  });

  it('given an OS is provided, includes it in parentheses', () => {
    // ACT
    const result = buildEmulatorClientLabel(
      createParsedUserAgent({
        client: 'RALibRetro',
        clientVersion: '1.19.1',
        os: 'Windows 8 x64 Build 9200 6.2',
      }),
    );

    // ASSERT
    expect(result).toEqual('RALibRetro 1.19.1 (Windows 8 x64 Build 9200 6.2)');
  });

  it('given all fields are provided, returns the complete label format', () => {
    // ACT
    const result = buildEmulatorClientLabel(
      createParsedUserAgent({
        client: 'RALibRetro',
        clientVersion: '1.19.1',
        clientVariation: 'mesen',
        os: 'Windows 8 x64 Build 9200 6.2',
      }),
    );

    // ASSERT
    expect(result).toEqual('RALibRetro 1.19.1 - mesen (Windows 8 x64 Build 9200 6.2)');
  });
});
