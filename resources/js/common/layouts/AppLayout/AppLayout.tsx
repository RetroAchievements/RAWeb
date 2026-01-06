import { type FC, type ReactNode } from 'react';

import { cn } from '@/common/utils/cn';

interface AppLayoutBaseProps {
  children: ReactNode;
  withSidebar: boolean;
}

const AppLayoutBase: FC<AppLayoutBaseProps> = ({ children, withSidebar }) => {
  return (
    <div className="container lg:max-w-none xl:max-w-screen-xl">
      <main className={withSidebar ? 'with-sidebar' : undefined} data-scroll-target>
        {children}
      </main>
    </div>
  );
};

interface AppLayoutMainProps {
  children: ReactNode;

  className?: string;
}

const AppLayoutMain: FC<AppLayoutMainProps> = ({ children, className }) => {
  return (
    <article className={cn('relative z-10 !px-2.5 sm:!px-4 md:!px-5', className)}>
      {children}
    </article>
  );
};

interface AppLayoutSidebarProps {
  children: ReactNode;
}

const AppLayoutSidebar: FC<AppLayoutSidebarProps> = ({ children }) => {
  return <aside className="relative z-10">{children}</aside>;
};

interface AppLayoutBannerProps {
  children: ReactNode;

  className?: string;
}

const AppLayoutBanner: FC<AppLayoutBannerProps> = ({ children, className }) => {
  return <div className={cn('col-span-full', className)}>{children}</div>;
};

export const AppLayout = Object.assign(AppLayoutBase, {
  Banner: AppLayoutBanner,
  Main: AppLayoutMain,
  Sidebar: AppLayoutSidebar,
});
