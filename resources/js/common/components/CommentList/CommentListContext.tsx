import { createContext, type FC, type ReactNode, useContext } from 'react';

import type { ArticleType } from '@/common/utils/generatedAppConstants';

interface CommentListContextValue {
  commentableId: number;
  commentableType: keyof typeof ArticleType;

  onDeleteSuccess?: () => void;
  onSubmitSuccess?: () => void;
}

const CommentListContext = createContext<CommentListContextValue | undefined>(undefined);

export function useCommentListContext() {
  const context = useContext(CommentListContext);

  if (!context) {
    throw new Error('useCommentListContext must be used within a CommentListProvider');
  }

  return context;
}

type CommentListProviderProps = CommentListContextValue & { children: ReactNode };

export const CommentListProvider: FC<CommentListProviderProps> = ({ children, ...props }) => (
  <CommentListContext.Provider value={props}>{children}</CommentListContext.Provider>
);
