/**
 * Creates a PaginatedData object with the given items.
 *
 * @template TItems - The type of the items in the paginated data.
 * @param {TItems[]} items - An array of items to include in the paginated data.
 * @returns {App.Data.PaginatedData<TItems>} The paginated data object containing the provided items.
 *
 * @example
 * // Create an array of User items
 * const users: App.Data.User[] = [
 *   createUser(),
 *   createUser(),
 * ];
 *
 * // Create paginated data for the users
 * const paginatedUsers = createPaginatedData(users);
 * console.log(paginatedUsers);
 * // Output:
 * // {
 * //   currentPage: 1,
 * //   items: [... created users ...],
 * //   lastPage: 9999,
 * //   links: { first: null, last: null, next: null, previous: null },
 * //   perPage: 25,
 * //   total: 25000,
 * //   unfilteredTotal: null
 * // }
 */
export const createPaginatedData = <TItems>(
  items: TItems[],
  overrides?: Partial<App.Data.PaginatedData<TItems>>,
): App.Data.PaginatedData<TItems> => {
  return {
    currentPage: 1,
    items,
    lastPage: 9999,
    links: {
      firstPageUrl: null,
      lastPageUrl: null,
      nextPageUrl: null,
      previousPageUrl: null,
    },
    perPage: 25,
    total: 25000,
    unfilteredTotal: null,

    ...overrides,
  };
};
