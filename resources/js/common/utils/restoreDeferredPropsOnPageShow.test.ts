import { router } from '@inertiajs/react';

import { restoreDeferredPropsOnPageShow } from './restoreDeferredPropsOnPageShow';

vi.mock('@inertiajs/react', () => ({
  router: { reload: vi.fn() },
}));

function setHistoryPage(page: unknown): void {
  window.history.replaceState({ page }, '', window.location.href);
}

function firePageShow(persisted: boolean): void {
  restoreDeferredPropsOnPageShow({ persisted } as PageTransitionEvent);
}

describe('Util: restoreDeferredPropsOnPageShow', () => {
  beforeEach(() => {
    setHistoryPage(undefined);
  });

  it('is defined', () => {
    // ASSERT
    expect(restoreDeferredPropsOnPageShow).toBeDefined();
  });

  it('given the restore did not come from the back/forward cache, does not reload', () => {
    // ARRANGE
    setHistoryPage({ props: {}, initialDeferredProps: { default: ['screenshots'] } });

    // ACT
    firePageShow(false);

    // ASSERT
    expect(router.reload).not.toHaveBeenCalled();
  });

  it('given the history entry has no page, does not reload', () => {
    // ACT
    firePageShow(true);

    // ASSERT
    expect(router.reload).not.toHaveBeenCalled();
  });

  it('given the history entry is encrypted, does not reload', () => {
    // ARRANGE
    setHistoryPage('encrypted-blob');

    // ACT
    firePageShow(true);

    // ASSERT
    expect(router.reload).not.toHaveBeenCalled();
  });

  it('given the page declared no deferred props, does not reload', () => {
    // ARRANGE
    setHistoryPage({ props: { screenshots: [] } });

    // ACT
    firePageShow(true);

    // ASSERT
    expect(router.reload).not.toHaveBeenCalled();
  });

  it('given every deferred prop arrived, does not reload', () => {
    // ARRANGE
    setHistoryPage({
      props: { allLeaderboards: [], screenshots: [{ id: 1 }] },
      initialDeferredProps: { default: ['allLeaderboards', 'screenshots'] },
    });

    // ACT
    firePageShow(true);

    // ASSERT
    expect(router.reload).not.toHaveBeenCalled();
  });

  it('given a deferred prop never arrived, re-requests only that prop', () => {
    // ARRANGE
    setHistoryPage({
      props: { allLeaderboards: [] },
      initialDeferredProps: { default: ['allLeaderboards', 'screenshots'] },
    });

    // ACT
    firePageShow(true);

    // ASSERT
    expect(router.reload).toHaveBeenCalledOnce();
    expect(router.reload).toHaveBeenCalledWith({ only: ['screenshots'], preserveErrors: true });
  });

  it('given the page only reports deferredProps, still re-requests the missing prop', () => {
    // ARRANGE
    setHistoryPage({ props: {}, deferredProps: { default: ['screenshots'] } });

    // ACT
    firePageShow(true);

    // ASSERT
    expect(router.reload).toHaveBeenCalledWith({ only: ['screenshots'], preserveErrors: true });
  });

  it('given a deferred prop path is nested, resolves it through the parent prop', () => {
    // ARRANGE
    setHistoryPage({
      props: { game: { badges: [] } },
      initialDeferredProps: { default: ['game.badges', 'game.hashes'] },
    });

    // ACT
    firePageShow(true);

    // ASSERT
    expect(router.reload).toHaveBeenCalledWith({ only: ['game.hashes'], preserveErrors: true });
  });

  it('given the page declared multiple deferred groups, requests each group separately', () => {
    // ARRANGE
    setHistoryPage({
      props: {},
      initialDeferredProps: { default: ['screenshots'], slow: ['allLeaderboards'] },
    });

    // ACT
    firePageShow(true);

    // ASSERT
    expect(router.reload).toHaveBeenCalledTimes(2);
    expect(router.reload).toHaveBeenCalledWith({ only: ['screenshots'], preserveErrors: true });
    expect(router.reload).toHaveBeenCalledWith({
      only: ['allLeaderboards'],
      preserveErrors: true,
    });
  });
});
