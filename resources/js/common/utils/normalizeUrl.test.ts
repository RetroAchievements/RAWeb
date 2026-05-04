import { normalizeUrl } from './normalizeUrl';

console.debug = vi.fn();

describe('Util: normalizeUrl', () => {
  it('given an empty URL, returns an empty string', () => {
    // ASSERT
    expect(normalizeUrl('')).toEqual('');
  });

  it('given an HTTPS URL, returns the normalized URL', () => {
    // ASSERT
    expect(normalizeUrl('https://example.com')).toEqual('https://example.com/');
  });

  it('given a URL without a scheme, assumes HTTPS', () => {
    // ASSERT
    expect(normalizeUrl('example.com/path')).toEqual('https://example.com/path');
  });

  it('given an HTTP internal URL, upgrades it to HTTPS', () => {
    // ASSERT
    expect(normalizeUrl('http://retroachievements.org/game/1/top-achievers')).toEqual(
      'https://retroachievements.org/game/1/top-achievers',
    );
  });

  it('given a malformed URL, returns an empty string', () => {
    // ASSERT
    expect(normalizeUrl('<a')).toEqual('');
  });

  it('given a dangerous scheme, returns an empty string', () => {
    // ASSERT
    expect(normalizeUrl('javascript:alert(1)')).toEqual('');
    expect(normalizeUrl('data:text/html,<script>alert(1)</script>')).toEqual('');
  });
});
