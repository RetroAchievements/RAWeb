export interface RecentForumPost {
  authorDisplayName: string;
  commentCountDay: number | null;
  commentCountWeek: number | null;
  commentId: number;
  commentIdDay: number | null;
  commentIdWeek: number | null;
  forumTopicId: number;
  forumTopicTitle: string;
  postedAt: string;
  shortMessage: string;
}
