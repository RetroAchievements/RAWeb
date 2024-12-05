import type { FC } from 'react';

interface PageMetaDescriptionProps {
  /** This is deliberately untranslated. */
  content: string;
}

export const PageMetaDescription: FC<PageMetaDescriptionProps> = ({ content }) => {
  // Google doesn't like meta descriptions longer than this.
  if (content.length >= 210) {
    console.error('The description content for this page is too long. Please shorten it.');
  }

  return (
    <>
      <meta name="description" content={content} />
      <meta name="og:description" content={content} />
    </>
  );
};
