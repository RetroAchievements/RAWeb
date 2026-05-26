import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { normalizeUrl } from '@/common/utils/normalizeUrl';

import { processVideoUrl } from '../../utils/shortcodes/processVideoUrl';

const baseUrl = import.meta.env.VITE_BASE_URL;

interface VideoEmbedProps {
  src: string;
}

export const VideoEmbed: FC<VideoEmbedProps> = ({ src }) => {
  const { t } = useTranslation();

  const processedVideo = processVideoUrl(src);

  if (!processedVideo) {
    const normalizedSrc = normalizeUrl(src);

    if (!normalizedSrc) {
      return <span className="line-through">{t('Broken Link')}</span>;
    }

    return (
      <a
        href={route('redirect', { url: normalizedSrc })}
        target="_blank"
        rel="noreferrer noopener nofollow"
      >
        {src}
      </a>
    );
  }

  let embedUrl = '';
  switch (processedVideo.type) {
    case 'youtube': {
      const params = new URLSearchParams(processedVideo.params);
      embedUrl = `//www.youtube-nocookie.com/embed/${processedVideo.videoId}${params.toString() ? '?' + params.toString() : ''}`;
      break;
    }

    case 'twitch-video': {
      const params = new URLSearchParams({
        video: processedVideo.videoId,
        parent: baseUrl,
        autoplay: 'false',
      });
      embedUrl = `//player.twitch.tv/?${params.toString()}`;
      break;
    }

    case 'twitch-collection': {
      const params = new URLSearchParams({
        collection: processedVideo.videoId,
        parent: baseUrl,
        autoplay: 'false',
      });
      embedUrl = `//player.twitch.tv/?${params.toString()}`;
      break;
    }

    case 'twitch-clip': {
      const params = new URLSearchParams({
        clip: processedVideo.videoId,
        parent: baseUrl,
        autoplay: 'false',
      });
      embedUrl = `//clips.twitch.tv/embed?${params.toString()}`;
      break;
    }
  }

  return (
    <div data-testid="video-embed" className="embed-responsive aspect-16/9">
      {/* eslint-disable-next-line jsx-a11y/iframe-has-title -- the video URL doesn't carry a title */}
      <iframe
        data-testid="video-embed-iframe"
        className="embed-responsive-item"
        src={embedUrl}
        allowFullScreen={true}
      />
    </div>
  );
};
