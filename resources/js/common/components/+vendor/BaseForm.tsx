/* eslint-disable no-restricted-imports -- base components can import from @radix-ui */

import type * as LabelPrimitive from '@radix-ui/react-label';
import { Slot } from '@radix-ui/react-slot';
import * as React from 'react';
import type { ControllerProps, FieldPath, FieldValues } from 'react-hook-form';
import { Controller, FormProvider, useFormContext } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import { cn } from '@/utils/cn';

import { BaseLabel } from './BaseLabel';

const BaseFormProvider = FormProvider;

type BaseFormFieldContextValue<
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
> = {
  name: TName;
};

const BaseFormFieldContext = React.createContext<BaseFormFieldContextValue>(
  {} as BaseFormFieldContextValue,
);

const BaseFormField = <
  TFieldValues extends FieldValues = FieldValues,
  TName extends FieldPath<TFieldValues> = FieldPath<TFieldValues>,
>({
  ...props
}: ControllerProps<TFieldValues, TName>) => {
  return (
    <BaseFormFieldContext.Provider value={{ name: props.name }}>
      <Controller {...props} />
    </BaseFormFieldContext.Provider>
  );
};

const useBaseFormField = () => {
  const fieldContext = React.useContext(BaseFormFieldContext);
  const itemContext = React.useContext(BaseFormItemContext);
  const { getFieldState, formState } = useFormContext();

  const fieldState = getFieldState(fieldContext.name, formState);

  if (!fieldContext) {
    throw new Error('useFormField should be used within <FormField>');
  }

  const { id } = itemContext;

  return {
    id,
    name: fieldContext.name,
    formItemId: `${id}-form-item`,
    formDescriptionId: `${id}-form-item-description`,
    formMessageId: `${id}-form-item-message`,
    ...fieldState,
  };
};

type BaseFormItemContextValue = {
  id: string;
};

const BaseFormItemContext = React.createContext<BaseFormItemContextValue>(
  {} as BaseFormItemContextValue,
);

const BaseFormItem = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => {
    const id = React.useId();

    return (
      <BaseFormItemContext.Provider value={{ id }}>
        <div ref={ref} className={cn(className)} {...props} />
      </BaseFormItemContext.Provider>
    );
  },
);
BaseFormItem.displayName = 'BaseFormItem';

const BaseFormLabel = React.forwardRef<
  React.ElementRef<typeof LabelPrimitive.Root>,
  React.ComponentPropsWithoutRef<typeof LabelPrimitive.Root>
>(({ className, ...props }, ref) => {
  const { error, formItemId } = useBaseFormField();

  return (
    <BaseLabel
      ref={ref}
      className={cn(error ? 'text-red-500' : 'text-menu-link', className)}
      htmlFor={formItemId}
      {...props}
    />
  );
});
BaseFormLabel.displayName = 'BaseFormLabel';

const BaseFormControl = React.forwardRef<
  React.ElementRef<typeof Slot>,
  React.ComponentPropsWithoutRef<typeof Slot>
>(({ ...props }, ref) => {
  const { error, formItemId, formDescriptionId, formMessageId } = useBaseFormField();

  return (
    <Slot
      ref={ref}
      id={formItemId}
      aria-describedby={!error ? `${formDescriptionId}` : `${formDescriptionId} ${formMessageId}`}
      aria-invalid={!!error}
      {...props}
    />
  );
});
BaseFormControl.displayName = 'BaseFormControl';

const BaseFormDescription = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLParagraphElement>
>(({ className, ...props }, ref) => {
  const { formDescriptionId } = useBaseFormField();

  return (
    <p ref={ref} id={formDescriptionId} className={cn('text-neutral-500', className)} {...props} />
  );
});
BaseFormDescription.displayName = 'BaseFormDescription';

const BaseFormMessage = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLParagraphElement>
>(({ className, children, ...props }, ref) => {
  const { t } = useTranslation();
  const { error, formMessageId } = useBaseFormField();
  const body = error ? String(error?.message) : children;

  if (!body) {
    return null;
  }

  return (
    <p
      ref={ref}
      id={formMessageId}
      className={cn('text-sm font-medium text-red-500', className)}
      {...props}
    >
      {children ?? t(body as string)}
    </p>
  );
});
BaseFormMessage.displayName = 'BaseFormMessage';

export {
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
  BaseFormProvider,
  useBaseFormField,
};
