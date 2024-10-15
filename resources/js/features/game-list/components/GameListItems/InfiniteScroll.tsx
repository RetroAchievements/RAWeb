import { type FC } from 'react';
import { useInView } from 'react-intersection-observer';

interface InfiniteScrollProps {
  loadMore: () => unknown;
}

export const InfiniteScroll: FC<InfiniteScrollProps> = ({ loadMore }) => {
  const [ref] = useInView({
    rootMargin: '300px',
    onChange: (inView) => {
      if (inView) {
        loadMore();
      }
    },
  });

  return <div className="h-px" ref={ref} />;
};
