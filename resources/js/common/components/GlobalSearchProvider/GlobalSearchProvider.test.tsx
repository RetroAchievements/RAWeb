import { fireEvent, render, screen, waitFor } from '@/test';

import { GlobalSearchProvider } from './GlobalSearchProvider';

const { renderGlobalSearchSpy } = vi.hoisted(() => ({
  renderGlobalSearchSpy: vi.fn(),
}));

vi.mock('../GlobalSearch', () => ({
  GlobalSearch: ({
    isOpen,
    onOpenChange,
  }: {
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
  }) => {
    renderGlobalSearchSpy(isOpen);

    return isOpen ? (
      <button type="button" data-testid="global-search" onClick={() => onOpenChange(false)}>
        Global Search
      </button>
    ) : null;
  },
}));

describe('Component: GlobalSearchProvider', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('does not render the global search dialog until the user opens it', () => {
    // ARRANGE
    render(
      <GlobalSearchProvider>
        <div>Page content</div>
      </GlobalSearchProvider>,
      { wrapper: ({ children }) => <>{children}</> },
    );

    // ASSERT
    expect(screen.getByText(/page content/i)).toBeVisible();
    expect(screen.queryByTestId('global-search')).not.toBeInTheDocument();
    expect(renderGlobalSearchSpy).not.toHaveBeenCalled();
  });

  it('opens the global search dialog from the keyboard shortcut', async () => {
    // ARRANGE
    render(
      <GlobalSearchProvider>
        <div>Page content</div>
      </GlobalSearchProvider>,
      { wrapper: ({ children }) => <>{children}</> },
    );

    // ACT
    fireEvent.keyDown(document, { code: 'KeyK', metaKey: true });

    // ASSERT
    expect(await screen.findByTestId('global-search')).toBeVisible();
    expect(renderGlobalSearchSpy).toHaveBeenCalledWith(true);
  });

  it('closes the global search dialog when the dialog requests close', async () => {
    // ARRANGE
    render(
      <GlobalSearchProvider>
        <div>Page content</div>
      </GlobalSearchProvider>,
      { wrapper: ({ children }) => <>{children}</> },
    );

    fireEvent.keyDown(document, { code: 'KeyK', metaKey: true });

    const searchDialog = await screen.findByTestId('global-search');

    // ACT
    fireEvent.click(searchDialog);

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByTestId('global-search')).not.toBeInTheDocument();
    });
  });
});
