import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

import { render } from '@/test';

import { InfiniteScrollLoadMore } from './InfiniteScrollLoadMore';

describe('Component: InfiniteScrollLoadMore', () => {
  it('renders without crashing', () => {
    // ARRANGE
    mockAllIsIntersecting(false);

    const { container } = render(<InfiniteScrollLoadMore onLoadMore={vi.fn()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('emits an event', () => {
    // ARRANGE
    const onLoadMore = vi.fn();

    render(<InfiniteScrollLoadMore onLoadMore={onLoadMore} />);

    mockAllIsIntersecting(true);

    // ASSERT
    expect(onLoadMore).toHaveBeenCalledOnce();
  });
});
