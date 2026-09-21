import { HTMLAttributes, forwardRef } from 'react';
import { cn } from './utils';

export interface NeuCardProps extends HTMLAttributes<HTMLDivElement> {
  elevation?: 'flat' | 'raised-sm' | 'raised-md' | 'raised-lg' | 'pressed';
  radius?: 'sm' | 'md' | 'lg' | 'pill';
  padding?: 'none' | 'sm' | 'md' | 'lg';
  as?: React.ElementType;
}

export const NeuCard = forwardRef<HTMLDivElement, NeuCardProps>(
  (
    {
      className,
      elevation = 'raised-md',
      radius = 'md',
      padding = 'md',
      as: Component = 'div',
      ...props
    },
    ref
  ) => {
    const radiusClass = {
      sm: 'rounded-neu-sm',
      md: 'rounded-neu-md',
      lg: 'rounded-neu-lg',
      pill: 'rounded-neu-pill',
    }[radius];

    const paddingClass = {
      none: 'p-0',
      sm: 'p-4',
      md: 'p-5',
      lg: 'p-8',
    }[padding];

    const elevationClass = {
      'flat': 'neu-flat',
      'raised-sm': 'neu-raised-sm',
      'raised-md': 'neu-raised-md',
      'raised-lg': 'neu-raised-lg',
      'pressed': 'neu-pressed',
    }[elevation];

    return (
      <Component
        ref={ref}
        className={cn(
          'bg-neu-surface text-neu-text',
          radiusClass,
          paddingClass,
          elevationClass,
          className
        )}
        {...props}
      />
    );
  }
);
NeuCard.displayName = 'NeuCard';
