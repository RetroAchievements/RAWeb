import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { ShortcodeSpoiler } from './ShortcodeSpoiler';

describe('Component: ShortcodeSpoiler', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeSpoiler>Test content</ShortcodeSpoiler>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the component is rendered, shows a button labeled Spoiler', () => {
    // ARRANGE
    render(<ShortcodeSpoiler>Test content</ShortcodeSpoiler>);

    // ASSERT
    expect(screen.getByRole('button', { name: /spoiler/i })).toBeVisible();
  });

  it('given the user clicks the Spoiler button, reveals the hidden content', async () => {
    // ARRANGE
    render(<ShortcodeSpoiler>Hidden spoiler content</ShortcodeSpoiler>);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /spoiler/i }));

    // ASSERT
    expect(screen.getByText(/hidden spoiler content/i)).toBeVisible();
  });
});
