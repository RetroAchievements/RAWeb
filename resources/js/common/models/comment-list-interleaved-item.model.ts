import { type ReactNode } from 'react';

export interface CommentListInterleavedItem {
  createdAt: string;
  key: string;
  node: ReactNode;
}
