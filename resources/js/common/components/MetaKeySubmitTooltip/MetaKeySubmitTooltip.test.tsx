import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { MetaKeySubmitTooltip } from './MetaKeySubmitTooltip';

describe('Component: MetaKeySubmitTooltip', () => {
  it('renders without crashing', () => {
    // ASSERT
    render(
      <MetaKeySubmitTooltip>
        <button>Submit</button>
      </MetaKeySubmitTooltip>,
    );
  });

  it('given the tooltip is hovered, displays the meta key shortcut', async () => {
    // ARRANGE
    render(
      <MetaKeySubmitTooltip>
        <button>Submit</button>
      </MetaKeySubmitTooltip>,
      { pageProps: { metaKey: 'Ctrl' } },
    );

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/ctrl \+ enter/i)[0]).toBeVisible();
    });
  });
});
