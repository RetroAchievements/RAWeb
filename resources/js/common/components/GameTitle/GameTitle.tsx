import { type FC, Fragment } from 'react';

interface GameTitleProps {
  title: string;

  showTags?: boolean;
}

export const GameTitle: FC<GameTitleProps> = ({ title, showTags = true }) => {
  const { subsetKind, nonSubsetTags, strippedTitle } = stripTagsFromTitle(title);

  return (
    <span>
      {strippedTitle}

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
                <span className="tag-label">Subset</span>
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
