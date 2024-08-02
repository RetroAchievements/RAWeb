import { type FC, type ReactNode } from 'react';

interface AppLayoutBaseProps {
  children: ReactNode;
  withSidebar: boolean;
}

const AppLayoutBase: FC<AppLayoutBaseProps> = ({ children, withSidebar }) => {
  return (
    <div className="container">
      <main className={withSidebar ? 'with-sidebar' : undefined} data-scroll-target>
        {children}
      </main>
    </div>
  );
};

interface AppLayoutMainProps {
  children: ReactNode;
}

const AppLayoutMain: FC<AppLayoutMainProps> = ({ children }) => {
  return <article>{children}</article>;
};

interface AppLayoutSidebarProps {
  children: ReactNode;
}

const AppLayoutSidebar: FC<AppLayoutSidebarProps> = ({ children }) => {
  return <aside>{children}</aside>;
};

export const AppLayout = Object.assign(AppLayoutBase, {
  Main: AppLayoutMain,
  Sidebar: AppLayoutSidebar,
});
