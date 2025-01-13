import type { FC } from 'react';

import { processVideoUrl } from '@/features/forums/utils/processVideoUrl';

interface ShortcodeVideoProps {
  src: string;
}

export const ShortcodeVideo: FC<ShortcodeVideoProps> = ({ src }) => {
  const processedVideo = processVideoUrl(src);

  if (!processedVideo) {
    return null;
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
        parent: window.location.hostname,
        autoplay: 'false',
      });
      embedUrl = `//player.twitch.tv/?${params.toString()}`;
      break;
    }

    case 'twitch-collection': {
      const params = new URLSearchParams({
        collection: processedVideo.videoId,
        parent: window.location.hostname,
        autoplay: 'false',
      });
      embedUrl = `//player.twitch.tv/?${params.toString()}`;
      break;
    }

    case 'twitch-clip': {
      const params = new URLSearchParams({
        clip: processedVideo.videoId,
        parent: window.location.hostname,
        autoplay: 'false',
      });
      embedUrl = `//clips.twitch.tv/embed?${params.toString()}`;
      break;
    }
  }

  return (
    <div data-testid="video-embed" className="embed-responsive aspect-[16/9]">
      <iframe
        data-testid="video-embed-iframe"
        className="embed-responsive-item"
        src={embedUrl}
        allowFullScreen={true}
      />
    </div>
  );
};
