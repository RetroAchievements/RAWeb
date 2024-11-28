import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

export function useToggleSubscriptionMutation() {
  return useMutation({
    mutationFn: (props: {
      subjectId: number;
      subjectType: App.Community.Enums.SubscriptionSubjectType;
      newState: boolean;
    }) => {
      const pathParams = { subjectId: props.subjectId, subjectType: props.subjectType };

      if (props.newState === true) {
        return axios.post<App.Community.Data.Subscription>(
          route('api.subscription.store', pathParams),
        );
      }

      return axios.delete(route('api.subscription.destroy', pathParams));
    },
  });
}
