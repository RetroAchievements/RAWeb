import { suggestTicketCloseAction } from './suggestTicketCloseAction';

describe('Util: suggestTicketCloseAction', () => {
  it.each([
    {
      note: 'Nestopia is not an officially supported core and should never be used for achievements.',
      expected: 'emulator-problem',
    },
    {
      note: 'I think it probably was a network issue at the time, since the logic is simple.',
      expected: 'network',
    },
    {
      note: 'You have to forge the equipment for the Hero, not the guest characters.',
      expected: 'closed-mistaken',
    },
    {
      note: 'The achievement works as intended. Cheats are not supported.',
      expected: 'closed-mistaken',
    },
  ] as const)('given the note "$note", points at $expected', ({ note, expected }) => {
    // ACT
    const result = suggestTicketCloseAction(note);

    // ASSERT
    expect(result).toEqual(expected);
  });

  it('given a note that could read as two different reasons, returns nothing', () => {
    // ACT
    const result = suggestTicketCloseAction(
      'Either the DuckStation build was too old or your connection dropped mid-session. Probably a network blip.',
    );

    // ASSERT
    expect(result).toBeNull();
  });

  it.each([
    "You must've done it before I fixed it a while ago.",
    'You must have changed that setting at some point.',
  ])('given a guess such as "%s" instead of an instruction, returns nothing', (note) => {
    // ACT
    const result = suggestTicketCloseAction(note);

    // ASSERT
    expect(result).toBeNull();
  });

  it('given a note with no standard reason in it, returns nothing', () => {
    // ACT
    const result = suggestTicketCloseAction(
      'This set is being revised next month, so I am closing this for now.',
    );

    // ASSERT
    expect(result).toBeNull();
  });

  it('given a word that merely contains a keyword, such as "score", returns nothing', () => {
    // ACT
    const result = suggestTicketCloseAction('Your score was saved before the final stage.');

    // ASSERT
    expect(result).toBeNull();
  });
});
