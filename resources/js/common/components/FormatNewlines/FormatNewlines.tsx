import { find } from 'linkifyjs';
import { type FC, Fragment, type ReactNode } from 'react';

import { buildUserContentLink } from '@/common/utils/buildUserContentLink';

interface FormatNewlinesProps {
  children: string;
}

export const FormatNewlines: FC<FormatNewlinesProps> = ({ children }) => {
  return children
    .trimEnd()
    .split('\n')
    .map((line, index) => (
      <Fragment key={index}>
        {renderTextWithLinks(line)}
        <br />
      </Fragment>
    ));
};

function renderTextWithLinks(textLine: string): ReactNode[] {
  const segments: ReactNode[] = [];
  let consumedUpTo = 0;

  for (const urlMatch of find(textLine, 'url', { defaultProtocol: 'https' })) {
    const parsedLink = buildUserContentLink(urlMatch.href);
    if (!parsedLink) {
      continue;
    }

    segments.push(textLine.slice(consumedUpTo, urlMatch.start));
    segments.push(
      <a
        key={urlMatch.start}
        href={parsedLink.href}
        rel={parsedLink.isExternal ? 'nofollow noopener' : undefined}
      >
        {urlMatch.value}
      </a>,
    );
    consumedUpTo = urlMatch.end;
  }

  segments.push(textLine.slice(consumedUpTo));

  return segments;
}
