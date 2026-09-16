import { getTicketListDefaultColumnVisibility } from './getTicketListDefaultColumnVisibility';

describe('Util: getTicketListDefaultColumnVisibility', () => {
  it('given the all scope, shows the columns that scope defaults to and hides the rest', () => {
    // ACT
    const result = getTicketListDefaultColumnVisibility('all', 'unresolved');

    // ASSERT
    expect(result).toEqual({
      id: true,
      ticketable: true,
      game: true,
      type: false,
      mode: false,
      developer: true,
      reporter: true,
      resolver: false,
      emulator: false,
      version: false,
      core: false,
      hash: false,
      age: true,
      resolvedAt: false,
    });
  });

  it('given the achievement scope, shows the issue type column instead of the game column', () => {
    // ACT
    const result = getTicketListDefaultColumnVisibility('achievement', 'unresolved');

    // ASSERT
    expect(result.type).toEqual(true);
    expect(result.game).toEqual(false);
    expect(result.ticketable).toEqual(false);
  });

  it('given a status that surfaces terminal tickets, shows the resolver column', () => {
    // ACT
    const resolvedResult = getTicketListDefaultColumnVisibility('all', 'resolved');
    const closedResult = getTicketListDefaultColumnVisibility('all', 'closed');
    const allResult = getTicketListDefaultColumnVisibility('all', 'all');
    const unresolvedResult = getTicketListDefaultColumnVisibility('all', 'unresolved');

    // ASSERT
    expect(resolvedResult.resolver).toEqual(true);
    expect(resolvedResult.resolvedAt).toEqual(true);
    expect(closedResult.resolver).toEqual(true);
    expect(closedResult.resolvedAt).toEqual(true);
    expect(allResult.resolver).toEqual(true);
    expect(allResult.resolvedAt).toEqual(true);
    expect(unresolvedResult.resolver).toEqual(false);
  });

  it('given the resolvedBy scope, never adds the resolver column', () => {
    // ACT
    const result = getTicketListDefaultColumnVisibility('resolvedBy', 'resolved');

    // ASSERT
    expect(result.resolver).toEqual(false);
  });

  it('given a resolution date sort, shows the resolved column in either direction', () => {
    // ACT
    const ascendingResult = getTicketListDefaultColumnVisibility('all', 'unresolved', 'resolvedAt');
    const descendingResult = getTicketListDefaultColumnVisibility(
      'all',
      'unresolved',
      '-resolvedAt',
    );

    // ASSERT
    expect(ascendingResult.resolvedAt).toEqual(true);
    expect(descendingResult.resolvedAt).toEqual(true);
    expect(ascendingResult.resolver).toEqual(false);
    expect(descendingResult.resolver).toEqual(false);
  });
});
