import { type FC } from 'react';
import { useInView } from 'react-intersection-observer';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';

import { SectionFormCard } from '../SectionFormCard';
import { useResetGameProgressForm } from './useResetGameProgressForm';

export const ResetGameProgressSectionCard: FC = () => {
  const {
    filteredAchievements,
    filteredGames,
    form,
    mutation,
    onSubmit,
    resettableGameAchievementsQuery,
    resettableGamesQuery,
    setIsResettableGamesQueryEnabled,
  } = useResetGameProgressForm();

  // Only fetch the list of resettable games when the control is visible.
  const { ref: inViewRef } = useInView({
    triggerOnce: true,
    initialInView: false,
    onChange: (inView) => {
      if (inView) {
        setIsResettableGamesQueryEnabled(true);
      }
    },
  });

  const [selectedGameId] = form.watch(['gameId']);

  return (
    <SectionFormCard
      headingLabel="Reset Game Progress"
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
      buttonProps={{
        children: 'Reset Progress',
        variant: 'destructive',
        disabled: !form.formState.isValid,
      }}
    >
      <div className="@container">
        <div className="@xl:gap-2 flex flex-col gap-5">
          <BaseFormField
            control={form.control}
            name="gameId"
            render={({ field }) => (
              <BaseFormItem className="@xl:grid @xl:grid-cols-5 @xl:items-center flex w-full flex-col gap-1">
                <BaseFormLabel className="col-span-2 text-menu-link">Game</BaseFormLabel>

                <div ref={inViewRef} className="col-span-3 flex flex-grow flex-col gap-1">
                  <BaseSelect value={field.value} onValueChange={field.onChange}>
                    <BaseFormControl>
                      <BaseSelectTrigger>
                        <BaseSelectValue placeholder="Select a game" />
                      </BaseSelectTrigger>
                    </BaseFormControl>

                    <BaseSelectContent>
                      {resettableGamesQuery.isFetched ? (
                        <>
                          {filteredGames.map((game) => (
                            <BaseSelectItem
                              key={`resettable-game-${game.id}`}
                              value={String(game.id)}
                            >
                              {game.title} ({game.consoleName}) ({game.numAwarded} /{' '}
                              {game.numPossible} won)
                            </BaseSelectItem>
                          ))}
                        </>
                      ) : (
                        <BaseSelectItem value="null" disabled>
                          Loading...
                        </BaseSelectItem>
                      )}
                    </BaseSelectContent>
                  </BaseSelect>
                </div>
              </BaseFormItem>
            )}
          />

          <BaseFormField
            control={form.control}
            name="achievementId"
            render={({ field }) => (
              <BaseFormItem className="@xl:grid @xl:grid-cols-5 @xl:items-center flex w-full flex-col gap-1">
                <BaseFormLabel className="col-span-2 text-menu-link">Achievement</BaseFormLabel>

                <div ref={inViewRef} className="col-span-3 flex flex-grow flex-col gap-1">
                  <BaseSelect
                    value={field.value}
                    onValueChange={field.onChange}
                    disabled={!selectedGameId}
                  >
                    <BaseFormControl>
                      <BaseSelectTrigger>
                        <BaseSelectValue placeholder="Select an achievement" />
                      </BaseSelectTrigger>
                    </BaseFormControl>

                    <BaseSelectContent>
                      <BaseSelectItem value="all">
                        All won achievements for this game
                      </BaseSelectItem>

                      {resettableGameAchievementsQuery.isFetched ? (
                        <>
                          {filteredAchievements.map((achievement) => (
                            <BaseSelectItem
                              key={`resettable-achievement-${achievement.id}`}
                              value={String(achievement.id)}
                            >
                              {achievement.title} ({achievement.points} points){' '}
                              {achievement.isHardcore ? '(Hardcore)' : null}
                            </BaseSelectItem>
                          ))}
                        </>
                      ) : (
                        <BaseSelectItem value="null" disabled>
                          Loading...
                        </BaseSelectItem>
                      )}
                    </BaseSelectContent>
                  </BaseSelect>
                </div>
              </BaseFormItem>
            )}
          />
        </div>
      </div>
    </SectionFormCard>
  );
};
