import { renderHook } from '@/test';

import { useCommentListContext } from './CommentListContext';

// We expect this test to throw, so ignore the error.
console.error = vi.fn();

describe('Hook: useCommentListContext', () => {
  it('throws an error when used outside of CommentListProvider', () => {
    // ASSERT
    expect(() => renderHook(() => useCommentListContext())).toThrow();
  });
});
