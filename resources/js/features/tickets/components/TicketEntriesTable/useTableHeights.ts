import { useLayoutEffect, useRef, useState } from 'react';

export function useTableHeights(visibleRowCount: number) {
  const measuredTableRef = useRef<HTMLTableElement>(null);
  const [measuredHeights, setMeasuredHeights] = useState<{
    collapsed: number;
    expanded: number;
  } | null>(null);

  useLayoutEffect(() => {
    const tableNode = measuredTableRef.current;
    if (!tableNode) {
      return;
    }

    const resizeWatcher = new ResizeObserver(() => {
      const firstClippedRow = tableNode.tBodies[0]?.rows[visibleRowCount];

      setMeasuredHeights({
        collapsed: firstClippedRow?.offsetTop ?? tableNode.offsetHeight,
        expanded: tableNode.offsetHeight,
      });
    });
    resizeWatcher.observe(tableNode);

    return () => resizeWatcher.disconnect();
  }, [visibleRowCount]);

  return { measuredHeights, measuredTableRef };
}
