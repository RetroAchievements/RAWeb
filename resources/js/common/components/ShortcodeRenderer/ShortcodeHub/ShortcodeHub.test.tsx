import { render, screen } from '@/test';
import { createGameSet } from '@/test/factories';

import { persistedHubsAtom } from '../../../state/shortcode.atoms';
import { ShortcodeHub } from './ShortcodeHub';

describe('Component: ShortcodeHub', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeHub hubId={1} />, {
      jotaiAtoms: [
        [persistedHubsAtom, []],
        //
      ],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the hub ID is not found in persisted hubs, renders nothing', () => {
    // ARRANGE
    render(<ShortcodeHub hubId={111111} />, {
      jotaiAtoms: [
        [persistedHubsAtom, [createGameSet({ id: 1 })]],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByTestId('hub-embed')).not.toBeInTheDocument();
  });

  it('given the hub ID is found in persisted hubs, renders the hub avatar', () => {
    // ARRANGE
    const hub = createGameSet({ id: 1, title: '[Central]' });

    render(<ShortcodeHub hubId={1} />, {
      jotaiAtoms: [
        [persistedHubsAtom, [hub]],
        //
      ],
    });

    // ASSERT
    expect(screen.getByTestId('hub-embed')).toBeVisible();

    expect(screen.getByRole('img', { name: /central/i })).toBeVisible();
    expect(screen.getByText(/central/i)).toBeVisible();
    expect(screen.getByRole('link')).toBeVisible();
  });

  it('links to the correct page', () => {
    // ARRANGE
    const hub = createGameSet({ id: 1, title: '[Central]' });

    render(<ShortcodeHub hubId={1} />, {
      jotaiAtoms: [
        [persistedHubsAtom, [hub]],
        //
      ],
    });

    // ASSERT
    const linkEl = screen.getByRole('link');
    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('hub.show'));
  });
});
