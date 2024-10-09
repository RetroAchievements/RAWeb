import type { FC, ReactNode } from 'react';

interface DataTablePaginationScrollTargetProps {
  children: ReactNode;
}

export const DataTablePaginationScrollTarget: FC<DataTablePaginationScrollTargetProps> = ({
  children,
}) => {
  return (
    <div id="pagination-scroll-target" className="scroll-mt-16">
      {children}
    </div>
  );
};
