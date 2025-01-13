import type { VideoType } from './video-type.model';

export interface ProcessedVideo {
  type: VideoType;
  videoId: string;
  params: Record<string, string>;
}
