import { describe, expect, it, type Mock, vi } from 'vitest';

import { fetcher } from './fetcher';

global.fetch = vi.fn();
document.querySelector = vi.fn();

describe('Util: fetcher', () => {
  it('is defined #sanity', () => {
    expect(fetcher).toBeDefined();
  });

  it('given a URL, can make a request', async () => {
    (fetch as Mock).mockResolvedValueOnce({
      ok: true,
      json: vi.fn().mockReturnValueOnce({ status: 'ok' }),
    });

    const response = await fetcher('/request/some-endpoint.php');

    expect(fetch).toHaveBeenCalledWith('/request/some-endpoint.php', expect.anything());
    expect(response).toEqual({ status: 'ok' });
  });

  it('correctly sets the X-CSRF-TOKEN header', async () => {
    // ARRANGE
    (fetch as Mock).mockResolvedValueOnce({
      ok: true,
      json: vi.fn().mockReturnValueOnce({ status: 'ok' }),
    });

    (document.querySelector as Mock).mockReturnValueOnce({
      getAttribute: vi.fn().mockReturnValue('test-csrf-token'),
    });

    // ACT
    await fetcher('/request/some-endpoint.php');

    // ASSERT
    expect(fetch).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        headers: expect.objectContaining({
          'X-CSRF-TOKEN': 'test-csrf-token',
        }),
      }),
    );
  });

  it('given an unsuccessful request, throws an error', async () => {
    (fetch as Mock).mockResolvedValueOnce({
      ok: false,
      status: 'failure',
    });

    await expect(fetcher('/request/some-endpoint.php')).rejects.toThrow(
      JSON.stringify({ ok: false, status: 'failure' }),
    );
  });

  it('given custom headers, merges them with the sane defaults', async () => {
    (fetch as Mock).mockResolvedValueOnce({
      ok: true,
      json: vi.fn(),
    });

    await fetcher('/request/some-endpoint.php', {
      headers: { Authorization: 'Bearer 12345' },
    });

    expect(fetch).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: 'Bearer 12345',
          'Content-Type': 'application/x-www-form-urlencoded',
        }),
      }),
    );
  });

  it('given a POST, makes the call with the provided body', async () => {
    // ARRANGE
    (fetch as Mock).mockResolvedValueOnce({
      ok: true,
      json: vi.fn().mockReturnValueOnce({ status: 'ok' }),
    });

    const formDataPayload = 'key=value&anotherKey=anotherValue';

    await fetcher('/request/some-endpoint.php', {
      method: 'POST',
      body: formDataPayload,
    });

    expect(fetch).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({
        method: 'POST',
        body: formDataPayload,
      }),
    );
  });
});
