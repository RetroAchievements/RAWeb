import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

import { useResettableGameAchievementsQuery } from './useResettableGameAchievementsQuery';
import { useResettableGamesQuery } from './useResettableGamesQuery';

const resetGameProgressFormSchema = z.object({
  gameId: z.string().min(1),
  achievementId: z.string().optional(),
});

export type FormValues = z.infer<typeof resetGameProgressFormSchema>;

export function useResetGameProgressForm() {
  const form = useForm<FormValues>({
    resolver: zodResolver(resetGameProgressFormSchema),
  });

  // When the user selects a game, instantly choose the "all won achievements"
  // option from the Achievement select field. This should reduce an extra click
  // for users looking to quickly wipe out a bunch of stuff.
  const [selectedGameId] = form.watch(['gameId']);
  useEffect(() => {
    if (selectedGameId) {
      form.setValue('achievementId', 'all');
    }
  }, [form, selectedGameId]);

  // The form component itself will control when this query is enabled.
  // Ideally, the query fires off when the form control is visible, not on mount.
  const [isResettableGamesQueryEnabled, setIsResettableGamesQueryEnabled] = useState(false);
  const resettableGamesQuery = useResettableGamesQuery(isResettableGamesQueryEnabled);

  const resettableGameAchievementsQuery = useResettableGameAchievementsQuery(selectedGameId);

  const [alreadyResetGameIds, setAlreadyResetGameIds] = useState<number[]>([]);
  const [alreadyResetAchievementIds, setAlreadyResetAchievementIds] = useState<number[]>([]);
  const [filteredGames, setFilteredGames] = useState<App.Platform.Data.PlayerResettableGame[]>([]);
  const [filteredAchievements, setFilteredAchievements] = useState<
    App.Platform.Data.PlayerResettableGameAchievement[]
  >([]);

  useEffect(() => {
    if (resettableGamesQuery.data) {
      setFilteredGames(
        resettableGamesQuery.data.filter((game) => !alreadyResetGameIds.includes(game.id)),
      );
    }
  }, [alreadyResetGameIds, resettableGamesQuery.data]);

  useEffect(() => {
    if (resettableGameAchievementsQuery.data) {
      setFilteredAchievements(
        resettableGameAchievementsQuery.data.filter(
          (achievement) => !alreadyResetAchievementIds.includes(achievement.id),
        ),
      );
    }
  }, [alreadyResetAchievementIds, resettableGameAchievementsQuery.data]);

  const mutation = useMutation({
    mutationFn: (payload: Partial<FormValues>) => {
      let url = '';
      if (payload.gameId) {
        url = route('user.game.destroy', payload.gameId);
      } else if (payload.achievementId) {
        url = route('user.achievement.destroy', payload.achievementId);
      }

      if (!url.length) {
        throw new Error('Nothing to reset.');
      }

      return axios.delete(url);
    },
    onSuccess: (_, variables) => {
      // After performing the mutation, store IDs for whatever we've wiped
      // out so the front-end knows it can stop rendering those things
      // as available for user selection.
      if (!variables.achievementId) {
        // Filter the cleared game IDs client-side. The request to actually
        // purge the game ID from the user's account progress is tied to
        // an async job that may take some time to finish, so we can't just
        // requery for the new list of games with progress.
        setAlreadyResetGameIds((prev) => [...prev, Number(variables.gameId)]);

        form.setValue('gameId', '');
        form.setValue('achievementId', '');
      } else {
        // Filter the cleared achievement IDs client-side. The request to actually
        // purge the achievement ID from the user's account progress is tied to
        // an async job that may take some time to finish, so we can't just
        // requery for the new list of achievements with progress associated to the playerGame.
        setAlreadyResetAchievementIds((prev) => [...prev, Number(variables.achievementId)]);

        // If the game has no achievements left, we need to consider the game as being reset
        // and ensure it's unselected from the form.
        const remainingAchievements = resettableGameAchievementsQuery.data?.filter(
          (achievement) =>
            !alreadyResetAchievementIds.includes(achievement.id) &&
            achievement.id !== Number(variables.achievementId),
        );
        if (!remainingAchievements || remainingAchievements.length === 0) {
          setAlreadyResetGameIds((prev) => [...prev, Number(variables.gameId)]);
          form.setValue('gameId', '');
        }

        form.setValue('achievementId', '');
      }
    },
  });

  const onSubmit = (formValues: FormValues) => {
    if (!confirm('Are you sure you want to reset this progress? This cannot be reversed.')) {
      return;
    }

    // We'll either send a game ID or an achievement ID to the server.
    // If we only send a game ID, the user wants to reset their progress for the whole game.
    const payload: Partial<FormValues> =
      formValues.achievementId === 'all'
        ? { gameId: formValues.gameId }
        : { achievementId: formValues.achievementId };

    toastMessage.promise(mutation.mutateAsync(payload), {
      loading: 'Resetting progress...',
      success: 'Progress was reset successfully.',
      error: 'Something went wrong.',
    });
  };

  return {
    alreadyResetAchievementIds,
    alreadyResetGameIds,
    filteredAchievements,
    filteredGames,
    form,
    mutation,
    onSubmit,
    resettableGameAchievementsQuery,
    resettableGamesQuery,
    setIsResettableGamesQueryEnabled,
  };
}
