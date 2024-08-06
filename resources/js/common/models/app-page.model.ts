import type { FC, ReactNode } from 'react';

type PageWithProps<TProps = unknown> = ReactNode & { props: TProps };
type InertiaLayoutFunction<TProps = unknown> = (page: PageWithProps<TProps>) => ReactNode;

export type AppPage<TProps = unknown> = FC<TProps> & { layout?: InertiaLayoutFunction<TProps> };
