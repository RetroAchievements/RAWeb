export type PlayerWithRank = App.Platform.Data.GameTopAchiever & {
  rankIndex: number;

  calculatedRank?: number;
};
