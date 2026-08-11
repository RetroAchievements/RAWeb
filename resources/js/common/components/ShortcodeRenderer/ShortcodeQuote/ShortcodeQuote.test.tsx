import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { ShortcodeQuote } from './ShortcodeQuote';

describe('Component: ShortcodeQuote', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeQuote>Test content</ShortcodeQuote>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a simple string child, renders it inside a div with the quotedtext class', () => {
    // ARRANGE
    render(<ShortcodeQuote>test content</ShortcodeQuote>);

    // ASSERT
    const quoteEl = screen.getByText(/test content/i);

    expect(quoteEl).toBeVisible();
    expect(quoteEl).toHaveClass('quotedtext');
    expect(quoteEl.nodeName).toEqual('DIV');
  });

  it('removes leading line breaks in the output', () => {
    // ARRANGE
    render(
      <ShortcodeQuote>
        <br />
        test content
      </ShortcodeQuote>,
    );

    // ASSERT
    const quoteEl = screen.getByText(/test/i);
    expect(quoteEl).toBeVisible();
    expect(quoteEl.innerHTML).toEqual('test content');
  });

  it('retains inner line breaks in the output', () => {
    // ARRANGE
    render(
      <ShortcodeQuote>
        test
        <br />
        content
      </ShortcodeQuote>,
    );

    // ASSERT
    const quoteEl = screen.getByText(/test/i);
    expect(quoteEl).toBeVisible();
    expect(quoteEl.innerHTML).toEqual('test<br>content');
  });

  it('given a top-level quote, does not show a collapse toggle', () => {
    // ARRANGE
    render(<ShortcodeQuote>Top-level quoted content</ShortcodeQuote>);

    // ASSERT
    expect(screen.getByText(/top-level quoted content/i)).toBeVisible();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given a quote nested inside another quote, collapses the nested quote behind a toggle', () => {
    // ARRANGE
    render(
      <ShortcodeQuote>
        Outer quoted content
        <ShortcodeQuote>Inner quoted content</ShortcodeQuote>
      </ShortcodeQuote>,
    );

    // ASSERT
    expect(screen.getByText(/outer quoted content/i)).toBeVisible();

    const toggleButton = screen.getByRole('button', { name: /quote/i });
    expect(toggleButton).toBeVisible();
    expect(toggleButton).toHaveAttribute('aria-expanded', 'false');
  });

  it('given the user clicks the toggle on a nested quote, reveals its content', async () => {
    // ARRANGE
    render(
      <ShortcodeQuote>
        Outer quoted content
        <ShortcodeQuote>Inner quoted content</ShortcodeQuote>
      </ShortcodeQuote>,
    );

    // ACT
    const toggleButton = screen.getByRole('button', { name: /quote/i });
    await userEvent.click(toggleButton);

    // ASSERT
    expect(screen.getByText(/inner quoted content/i)).toBeVisible();
    expect(toggleButton).toHaveAttribute('aria-expanded', 'true');
  });

  it('given a quote nested three levels deep, collapses every level past the outermost', () => {
    // ARRANGE
    render(
      <ShortcodeQuote>
        Level 1
        <ShortcodeQuote>
          Level 2
          <ShortcodeQuote>Level 3</ShortcodeQuote>
        </ShortcodeQuote>
      </ShortcodeQuote>,
    );

    // ASSERT
    expect(screen.getByText(/level 1/i)).toBeVisible();

    // !! two nested levels (2 and 3), so two collapsed toggles, both closed by default
    const toggleButtons = screen.getAllByRole('button', { name: /quote/i });
    expect(toggleButtons).toHaveLength(2);
    expect(toggleButtons[0]).toHaveAttribute('aria-expanded', 'false');
    expect(toggleButtons[1]).toHaveAttribute('aria-expanded', 'false');
  });
});
