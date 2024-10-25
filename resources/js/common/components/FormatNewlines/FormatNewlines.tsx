import { type FC, Fragment } from 'react';

interface FormatNewlinesProps {
  children: string;
}

export const FormatNewlines: FC<FormatNewlinesProps> = ({ children }) => {
  return children
    .trimEnd()
    .split('\n')
    .map((line, index) => (
      <Fragment key={index}>
        {line}
        <br />
      </Fragment>
    ));
};
