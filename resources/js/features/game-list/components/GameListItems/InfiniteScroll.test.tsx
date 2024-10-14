import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

import { render } from '@/test';

import { InfiniteScroll } from './InfiniteScroll';

describe('Component: InfiniteScroll', () => {
  it('renders without crashing', () => {
    // ARRANGE
    mockAllIsIntersecting(false);

    const { container } = render(<InfiniteScroll loadMore={vi.fn()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('emits an event', () => {
    // ARRANGE
    const loadMore = vi.fn();

    render(<InfiniteScroll loadMore={loadMore} />);

    mockAllIsIntersecting(true);

    // ASSERT
    expect(loadMore).toHaveBeenCalledTimes(1);
  });
});
