import { type FC } from 'react';
import { useInView } from 'react-intersection-observer';

interface InfiniteScrollLoadMoreProps {
  onLoadMore: () => unknown;

  /**
   * Margin around the root.
   * Can have values similar to the CSS margin property.
   *
   * @example "10px 20px 30px 40px" (top, right, bottom, left)
   */
  rootMargin?: string;
}

export const InfiniteScrollLoadMore: FC<InfiniteScrollLoadMoreProps> = ({
  onLoadMore,
  rootMargin = '300px',
}) => {
  const [ref] = useInView({
    rootMargin,
    onChange: (inView) => {
      if (inView) {
        onLoadMore();
      }
    },
  });

  return <div className="h-px" ref={ref} />;
};
