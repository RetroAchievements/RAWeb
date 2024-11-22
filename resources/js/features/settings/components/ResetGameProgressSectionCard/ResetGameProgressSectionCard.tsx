import { type FC } from 'react';
import { useTranslation } from 'react-i18next';
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
  const { t } = useTranslation();

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
      t_headingLabel={t('Reset Game Progress')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
      buttonProps={{
        children: t('Reset Progress'),
        variant: 'destructive',
        disabled: !form.formState.isValid,
      }}
    >
      <div className="@container">
        <div className="flex flex-col gap-5 @xl:gap-2">
          <BaseFormField
            control={form.control}
            name="gameId"
            render={({ field }) => (
              <BaseFormItem className="flex w-full flex-col gap-1 @xl:grid @xl:grid-cols-5 @xl:items-center">
                <BaseFormLabel
                  className="col-span-2 text-menu-link"
                  htmlFor="resettable-game-select"
                >
                  {t('Game')}
                </BaseFormLabel>

                <div ref={inViewRef} className="col-span-3 flex flex-grow flex-col gap-1">
                  <BaseSelect value={field.value} onValueChange={field.onChange}>
                    <BaseFormControl>
                      <BaseSelectTrigger id="resettable-game-select">
                        <BaseSelectValue placeholder={t('Select a game')} />
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
                              {t(
                                '{{gameTitle}} ({{consoleName}}) ({{numAwarded, number}} / {{numPossible, number}} won)',
                                {
                                  gameTitle: game.title,
                                  consoleName: game.consoleName,
                                  numAwarded: game.numAwarded,
                                  numPossible: game.numPossible,
                                },
                              )}
                            </BaseSelectItem>
                          ))}
                        </>
                      ) : (
                        <BaseSelectItem value="null" disabled>
                          {t('Loading...')}
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
              <BaseFormItem className="flex w-full flex-col gap-1 @xl:grid @xl:grid-cols-5 @xl:items-center">
                <BaseFormLabel
                  className="col-span-2 text-menu-link"
                  htmlFor="resettable-achievement-select"
                >
                  {t('Achievement')}
                </BaseFormLabel>

                <div ref={inViewRef} className="col-span-3 flex flex-grow flex-col gap-1">
                  <BaseSelect
                    value={field.value}
                    onValueChange={field.onChange}
                    disabled={!selectedGameId}
                  >
                    <BaseFormControl>
                      <BaseSelectTrigger id="resettable-achievement-select">
                        <BaseSelectValue placeholder={t('Select an achievement')} />
                      </BaseSelectTrigger>
                    </BaseFormControl>

                    <BaseSelectContent>
                      <BaseSelectItem value="all">
                        {t('All won achievements for this game')}
                      </BaseSelectItem>

                      {resettableGameAchievementsQuery.isFetched ? (
                        <>
                          {filteredAchievements.map((achievement) => (
                            <BaseSelectItem
                              key={`resettable-achievement-${achievement.id}`}
                              value={String(achievement.id)}
                            >
                              {t('{{achievementTitle}} ({{achievementPoints, number}} points)', {
                                achievementTitle: achievement.title,
                                achievementPoints: achievement.points,
                              })}{' '}
                              {achievement.isHardcore ? t('(Hardcore)') : null}
                            </BaseSelectItem>
                          ))}
                        </>
                      ) : (
                        <BaseSelectItem value="null" disabled>
                          {t('Loading...')}
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
