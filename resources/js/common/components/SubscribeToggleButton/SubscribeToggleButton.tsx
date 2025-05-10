import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBell, LuBellOff } from 'react-icons/lu';

import { BaseButton } from '../+vendor/BaseButton';
import { toastMessage } from '../+vendor/BaseToaster';
import { useToggleSubscriptionMutation } from './useToggleSubscriptionMutation';

/**
 * An `existingSubscription` might contain duplicative
 * `subjectId` and `subjectType` values, but we can't rely
 * on an existing subscription being present. Without these
 * values lifted out of the `Subscription` model, we won't have
 * anything to go by for users who have never subscribed to the
 * entity.
 */

interface SubscribeToggleButtonProps {
  hasExistingSubscription: boolean;
  subjectId: number;
  subjectType: App.Community.Enums.SubscriptionSubjectType;

  className?: string;
}

export const SubscribeToggleButton: FC<SubscribeToggleButtonProps> = ({
  hasExistingSubscription,
  subjectId,
  subjectType,
}) => {
  const { t } = useTranslation();

  const mutation = useToggleSubscriptionMutation();

  const [isSubscribed, setIsSubscribed] = useState(hasExistingSubscription);

  const label = isSubscribed ? t('Unsubscribe') : t('Subscribe');

  const handleClick = () => {
    const newState = !isSubscribed;

    toastMessage.promise(
      mutation.mutateAsync({ subjectId, subjectType, newState: !isSubscribed }),
      {
        loading: newState === true ? t('Subscribing...') : t('Unsubscribing...'),
        success: () => {
          setIsSubscribed(newState);

          return newState === true ? t('Subscribed!') : t('Unsubscribed!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return (
    <BaseButton size="sm" onClick={handleClick} className="gap-1.5" aria-label={label}>
      {isSubscribed ? <LuBellOff className="size-4" /> : <LuBell className="size-4" />}

      <span className="hidden sm:block">{label}</span>
    </BaseButton>
  );
};
