import { isSafeRedirectUri } from './isSafeRedirectUri';

describe('Util: isSafeRedirectUri', () => {
  it('is defined', () => {
    // ASSERT
    expect(isSafeRedirectUri).toBeDefined();
  });

  it('rejects wildcards and fragments, but accepts a plain https URI', () => {
    // ASSERT
    expect(isSafeRedirectUri('https://example.com/*/callback')).toEqual(false);
    expect(isSafeRedirectUri('https://example.com/callback#fragment')).toEqual(false);
    expect(isSafeRedirectUri('https://example.com/callback')).toEqual(true);
  });

  it('rejects a value that cannot be parsed as a URL', () => {
    // ASSERT
    expect(isSafeRedirectUri('not a url')).toEqual(false);
  });

  it('only accepts plaintext http for loopback hosts', () => {
    // ASSERT
    expect(isSafeRedirectUri('http://localhost:8000/callback')).toEqual(true);
    expect(isSafeRedirectUri('http://127.0.0.1/callback')).toEqual(true);
    expect(isSafeRedirectUri('http://example.com/callback')).toEqual(false);
  });

  it('leaves custom schemes to the server', () => {
    // ASSERT
    expect(isSafeRedirectUri('myapp://oauth/callback')).toEqual(true);
  });
});
