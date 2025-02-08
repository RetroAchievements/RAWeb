import presetReact from '@bbob/preset-react';
import BBCode from '@bbob/react';
import type { FC } from 'react';

import { bbobLineBreakPlugin } from '../../utils/+vendor/bbobLineBreakPlugin';
import { postProcessShortcodesInBody } from '../../utils/shortcodes/postProcessShortcodesInBody';
import { ShortcodeAch } from './ShortcodeAch';
import { ShortcodeCode } from './ShortcodeCode';
import { ShortcodeGame } from './ShortcodeGame';
import { ShortcodeHub } from './ShortcodeHub';
import { ShortcodeImg } from './ShortcodeImg';
import { ShortcodeQuote } from './ShortcodeQuote';
import { ShortcodeSpoiler } from './ShortcodeSpoiler';
import { ShortcodeTicket } from './ShortcodeTicket';
import { ShortcodeUrl } from './ShortcodeUrl';
import { ShortcodeUser } from './ShortcodeUser';
import { ShortcodeVideo } from './ShortcodeVideo';

const retroachievementsPreset = presetReact.extend((tags) => ({
  ...tags,

  url: (node) => {
    let href = '';
    if (node.attrs && Object.values(node.attrs).length) {
      href = Object.values(node.attrs as Record<string, string>)[0];
    } else {
      href = (node.content as string[]).join();
    }

    return {
      tag: ShortcodeUrl,
      attrs: {
        href,
        children: (node.content as string[]).join(),
      },
      content: node.content,
    };
  },

  code: (node) => ({
    ...node,
    tag: ShortcodeCode,
  }),

  quote: (node) => ({
    ...node,
    tag: ShortcodeQuote,
  }),

  spoiler: (node) => ({
    ...node,
    tag: ShortcodeSpoiler,
  }),

  img: (node) => {
    return {
      tag: ShortcodeImg,
      attrs: {
        src: node.content,
      },
    };
  },

  user: (node) => ({
    tag: ShortcodeUser,
    attrs: {
      displayName: (node.content as string[]).join(),
    },
  }),

  game: (node) => ({
    tag: ShortcodeGame,
    attrs: {
      gameId: Number((node.content as string[]).join()),
    },
  }),

  hub: (node) => ({
    tag: ShortcodeHub,
    attrs: {
      hubId: Number((node.content as string[]).join()),
    },
  }),

  ach: (node) => ({
    tag: ShortcodeAch,
    attrs: {
      achievementId: Number((node.content as string[]).join()),
    },
  }),

  ticket: (node) => ({
    tag: ShortcodeTicket,
    attrs: {
      ticketId: Number((node.content as string[]).join()),
    },
  }),

  video: (node) => {
    return {
      tag: ShortcodeVideo,
      attrs: {
        src: (node.content as string[]).join(),
      },
    };
  },
}));

const plugins = [retroachievementsPreset(), bbobLineBreakPlugin()];

interface ShortcodeRendererProps {
  body: string;
}

export const ShortcodeRenderer: FC<ShortcodeRendererProps> = ({ body }) => {
  return (
    <BBCode
      container="div"
      plugins={plugins}
      options={{
        onlyAllowTags: [
          'b',
          'i',
          'u',
          's',
          'code',
          'quote',
          'spoiler',
          'img',
          'url',
          'user',
          'ach',
          'game',
          'hub',
          'ticket',
          'video',
        ],
      }}
    >
      {postProcessShortcodesInBody(body)}
    </BBCode>
  );
};
