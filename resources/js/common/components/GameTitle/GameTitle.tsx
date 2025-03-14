import { type FC, Fragment } from 'react';
import { useTranslation } from 'react-i18next';

interface GameTitleProps {
  title: string;

  className?: string;
  showTags?: boolean;

  /**
   * Enables word-by-word wrapping for the game title text.
   * When enabled, each word in the title will be rendered as a separate
   * inline element, allowing the browser to wrap between any two words.
   * This is useful when the title appears inside clickable elements like
   * anchor tags, which otherwise might not wrap naturally.
   * @default false
   */
  isWordWrappingEnabled?: boolean;
}

export const GameTitle: FC<GameTitleProps> = ({
  title,
  className,
  showTags = true,
  isWordWrappingEnabled = false,
}) => {
  const { t } = useTranslation();

  const { subsetKind, nonSubsetTags, strippedTitle } = stripTagsFromTitle(title);

  return (
    <span className={className}>
      {isWordWrappingEnabled
        ? // Split title into words for proper wrapping on multiple lines.
          strippedTitle.split(' ').map((word, index) => (
            <Fragment key={`word-${index}`}>
              {index > 0 && ' '}
              <span className="inline">{word}</span>
            </Fragment>
          ))
        : strippedTitle}

      {showTags ? (
        <>
          {nonSubsetTags.map((tag) => (
            <Fragment key={`${strippedTitle}-${tag}`}>
              {' '}
              <span className="tag">
                <span>{tag}</span>
              </span>
            </Fragment>
          ))}

          {subsetKind ? (
            <>
              {' '}
              <span className="tag">
                <span className="tag-label">{t('Subset')}</span>
                <span className="tag-arrow" />
                <span>{subsetKind}</span>
              </span>
            </>
          ) : null}
        </>
      ) : null}
    </span>
  );
};

function stripTagsFromTitle(rawTitle: string): {
  subsetKind: string | null;
  nonSubsetTags: string[];
  strippedTitle: string;
} {
  let subsetKind: string | null = null;
  const nonSubsetTags: string[] = [];

  let strippedTitle = rawTitle;

  // Use a single regex operation to extract all tags in the format ~Tag~.
  const tagRegex = /~([^~]+)~/g;
  let match;
  while ((match = tagRegex.exec(strippedTitle)) !== null) {
    nonSubsetTags.push(match[1]);
  }
  strippedTitle = strippedTitle.replace(tagRegex, '');

  // Use a single regex operation to extract the subset.
  const subsetRegex = /\s?\[Subset - (.+)\]/;
  const subsetMatch = subsetRegex.exec(strippedTitle);
  if (subsetMatch) {
    subsetKind = subsetMatch[1];
    strippedTitle = strippedTitle.replace(subsetRegex, '');
  }

  strippedTitle = strippedTitle.trim();

  return {
    subsetKind,
    nonSubsetTags,
    strippedTitle,
  };
}
