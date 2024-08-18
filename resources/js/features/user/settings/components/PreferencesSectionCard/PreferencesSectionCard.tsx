import { usePage } from '@inertiajs/react';
import type { FC } from 'react';

import { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

import type { SettingsPageProps } from '../../models';
import { SectionFormCard } from '../SectionFormCard';
import { PreferencesSwitchField } from './PreferencesSwitchField';
import { usePreferencesSectionForm } from './usePreferencesSectionForm';

export const PreferencesSectionCard: FC = () => {
  const {
    props: { auth },
  } = usePage<SettingsPageProps>();

  const { form, mutation, onSubmit } = usePreferencesSectionForm(auth?.user.websitePrefs ?? 0);

  return (
    <SectionFormCard
      headingLabel="Preferences"
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
    >
      <div className="grid gap-x-36 gap-y-6 md:grid-cols-2">
        <PreferencesSwitchField
          label="Suppress mature content warnings"
          fieldName={StringifiedUserPreference.Site_SuppressMatureContentWarning}
          control={form.control}
        />

        <PreferencesSwitchField
          label="Show absolute dates on forum posts"
          fieldName={StringifiedUserPreference.Forum_ShowAbsoluteDates}
          control={form.control}
        />

        <PreferencesSwitchField
          label="Hide missable achievement indicators"
          fieldName={StringifiedUserPreference.Game_HideMissableIndicators}
          control={form.control}
        />

        <PreferencesSwitchField
          label="Only people I follow can message me or post on my wall"
          fieldName={StringifiedUserPreference.User_OnlyContactFromFollowing}
          control={form.control}
        />
      </div>
    </SectionFormCard>
  );
};
