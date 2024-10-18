import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC, useState } from 'react';

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
  existingSubscription: App.Community.Data.Subscription | null;
  subjectId: number;
  subjectType: App.Community.Enums.SubscriptionSubjectType;
}

export const SubscribeToggleButton: FC<SubscribeToggleButtonProps> = ({
  existingSubscription,
  subjectId,
  subjectType,
}) => {
  const { t } = useLaravelReactI18n();

  const mutation = useToggleSubscriptionMutation();

  const [isSubscribed, setIsSubscribed] = useState(existingSubscription?.state === true);

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
    <BaseButton size="sm" onClick={handleClick}>
      {isSubscribed ? t('Unsubscribe') : t('Subscribe')}
    </BaseButton>
  );
};
