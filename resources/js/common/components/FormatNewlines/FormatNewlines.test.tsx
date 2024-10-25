import { render } from '@/test';

import { FormatNewlines } from './FormatNewlines';

describe('Component: FormatNewlines', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<FormatNewlines>This is\nsome content</FormatNewlines>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('adds line breaks to text with newline characters', () => {
    // ARRANGE
    const { container } = render(<FormatNewlines>This is\nsome content</FormatNewlines>);

    // ASSERT
    // eslint-disable-next-line testing-library/no-container -- not queryable with testing-library
    const brEl = container.querySelectorAll('br');

    expect(brEl.length).toEqual(1);
  });
});
