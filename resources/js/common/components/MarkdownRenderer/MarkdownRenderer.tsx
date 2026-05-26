import type { FC } from 'react';
import ReactMarkdown from 'react-markdown';
import rehypeSanitize from 'rehype-sanitize';
import remarkGfm from 'remark-gfm';

interface MarkdownRendererProps {
  children?: string | null;
}

export const MarkdownRenderer: FC<MarkdownRendererProps> = ({ children }) => {
  return (
    <div className="prose prose-sm max-w-none prose-invert light:prose">
      <ReactMarkdown remarkPlugins={[remarkGfm]} rehypePlugins={[rehypeSanitize]}>
        {children}
      </ReactMarkdown>
    </div>
  );
};
