import { router } from '@inertiajs/react';
import axios from 'axios';
import { route } from 'ziggy-js';

import { ClaimStatus } from '@/common/utils/generatedAppConstants';
import { renderHook, waitFor } from '@/test';

import { useClaimActions } from './useClaimActions';

describe('Hook: useClaimActions', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useClaimActions());

    // ASSERT
    expect(result.current).toBeTruthy();
    expect(result.current.executeCreateClaim).toBeInstanceOf(Function);
    expect(result.current.executeDropClaim).toBeInstanceOf(Function);
    expect(result.current.executeExtendClaim).toBeInstanceOf(Function);
    expect(result.current.executeCompleteClaim).toBeInstanceOf(Function);
    expect(result.current.mutations).toBeDefined();
  });

  describe('Function: executeCreateClaim', () => {
    it('makes the correct API call', async () => {
      // ARRANGE
      const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
      vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
      const gameId = 123;

      const { result } = renderHook(() => useClaimActions());

      // ACT
      await result.current.executeCreateClaim(gameId);

      // ASSERT
      await waitFor(() => {
        expect(postSpy).toHaveBeenCalledOnce();
      });
      expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.create', { game: gameId }));
    });
  });

  describe('Function: executeDropClaim', () => {
    it('makes the correct API call', async () => {
      // ARRANGE
      const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
      vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
      const gameId = 456;

      const { result } = renderHook(() => useClaimActions());

      // ACT
      await result.current.executeDropClaim(gameId);

      // ASSERT
      await waitFor(() => {
        expect(postSpy).toHaveBeenCalledOnce();
      });
      expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.delete', { game: gameId }));
    });
  });

  describe('Function: executeExtendClaim', () => {
    it('makes the same API call as create claim', async () => {
      // ARRANGE
      const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
      vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
      const gameId = 789;

      const { result } = renderHook(() => useClaimActions());

      // ACT
      await result.current.executeExtendClaim(gameId);

      // ASSERT
      await waitFor(() => {
        expect(postSpy).toHaveBeenCalledOnce();
      });
      expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.create', { game: gameId }));
    });
  });

  describe('Function: executeCompleteClaim', () => {
    it('makes the correct API call with multipart form data', async () => {
      // ARRANGE
      const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
      vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
      const claimId = 999;

      const { result } = renderHook(() => useClaimActions());

      // ACT
      await result.current.executeCompleteClaim(claimId);

      // ASSERT
      await waitFor(() => {
        expect(postSpy).toHaveBeenCalledOnce();
      });

      const [url, formData, config] = postSpy.mock.calls[0];
      expect(url).toEqual(route('achievement-set-claim.update', { claim: claimId }));
      expect(formData).toBeInstanceOf(FormData);
      expect((formData as any).get('status')).toEqual(ClaimStatus.Complete.toString());
      expect(config).toEqual({
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    });
  });
});
