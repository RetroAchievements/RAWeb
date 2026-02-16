/**
 * Field-level change tracking (old/new values per field) was not
 * available before April 2022. Before this date, only generic
 * "Edited" entries exist in the achievement changelog.
 */
export const FIELD_LEVEL_TRACKING_CUTOFF = new Date('2022-04-01');
