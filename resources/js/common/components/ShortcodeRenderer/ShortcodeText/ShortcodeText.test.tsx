import { render, screen } from '@/test';

import { ShortcodeText } from './ShortcodeText';

describe('Component: ShortcodeText', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeText content="" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given plain text content without shortcodes, renders it unchanged', () => {
    // ARRANGE
    const text = 'Hello world';

    // ACT
    render(<ShortcodeText content={text} />);

    // ASSERT
    expect(screen.getByText(/hello world/i)).toBeVisible();
  });

  it('given content with a shortcode, converts curly braces to square brackets', () => {
    // ARRANGE
    const text = '{ach=}';

    // ACT
    render(<ShortcodeText content={text} />);

    // ASSERT
    expect(screen.getByText(/\[ach=\]/i)).toBeVisible();
  });

  it('given content with a closing shortcode tag, converts it properly', () => {
    // ARRANGE
    const text = '{tag}content{/tag}';

    // ACT
    render(<ShortcodeText content={text} />);

    // ASSERT
    expect(screen.getByText(/\[tag\]content\[\/tag\]/i)).toBeVisible();
  });
});
