export interface ShortcodeBodyPreviewMutationResponse {
  achievements: App.Platform.Data.Achievement[];
  convertedBody: string;
  events: App.Platform.Data.Event[];
  games: App.Platform.Data.Game[];
  hubs: App.Platform.Data.GameSet[];
  tickets: App.Platform.Data.Ticket[];
  users: App.Data.User[];
}
