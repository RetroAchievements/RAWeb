import { render, screen } from '@/test';

import { ShortcodeCode } from './ShortcodeCode';

describe('Component: ShortcodeCode', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeCode>test</ShortcodeCode>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a simple string child, renders it inside a span with the codetags class', () => {
    // ARRANGE
    render(<ShortcodeCode>test content</ShortcodeCode>);

    // ASSERT
    const spanEl = screen.getByText(/test content/i);

    expect(spanEl).toBeVisible();
    expect(spanEl).toHaveClass('codetags');
  });

  it('given multiple children including a leading br element, filters out the br element', () => {
    // ARRANGE
    render(<ShortcodeCode>{[<br key="br" />, 'test content', ' more content']}</ShortcodeCode>);

    // ASSERT
    const spanEl = screen.getByText(/test content more content/i);

    expect(spanEl).toBeVisible();
    expect(spanEl.innerHTML).toEqual('test content more content');
  });

  it('given an array of children with no br element, renders all content with the font-mono class', () => {
    // ARRANGE
    render(<ShortcodeCode>{['first content', ' second content']}</ShortcodeCode>);

    // ASSERT
    const spanEl = screen.getByText(/first content second content/i);

    expect(spanEl).toBeVisible();
    expect(spanEl).toHaveClass('font-mono');
    expect(spanEl).toHaveClass('codetags');
  });

  it('removes leading line breaks in the output', () => {
    // ARRANGE
    render(
      <ShortcodeCode>
        <br />
        test content
      </ShortcodeCode>,
    );

    // ASSERT
    const spanEl = screen.getByText(/test/i);
    expect(spanEl).toBeVisible();
    expect(spanEl.innerHTML).toEqual('test content');
  });

  it('retains inner line breaks in the output', () => {
    // ARRANGE
    render(
      <ShortcodeCode>
        test
        <br />
        content
      </ShortcodeCode>,
    );

    // ASSERT
    const spanEl = screen.getByText(/test/i);
    expect(spanEl).toBeVisible();
    expect(spanEl.innerHTML).toEqual('test<br>content');
  });
});
