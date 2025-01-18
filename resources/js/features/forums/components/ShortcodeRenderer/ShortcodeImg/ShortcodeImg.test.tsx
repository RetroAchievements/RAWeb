import { render, screen } from '@/test';

import { ShortcodeImg } from './ShortcodeImg';

describe('Component: ShortcodeImg', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeImg src="test.jpg" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a src prop, renders an image with that source', () => {
    // ARRANGE
    const testSrc = 'test-image.jpg';
    render(<ShortcodeImg src={testSrc} />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl.getAttribute('src')).toEqual(testSrc);
  });

  it('given a src prop, renders with the inline-image class', () => {
    // ARRANGE
    render(<ShortcodeImg src="test.jpg" />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).toHaveClass('inline-image');
  });
});
