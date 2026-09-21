import { ButtonHTMLAttributes, forwardRef } from 'react';
import { cn } from './utils';

export interface NeuButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
  size?: 'sm' | 'md' | 'lg' | 'icon';
  loading?: boolean;
}

export const NeuButton = forwardRef<HTMLButtonElement, NeuButtonProps>(
  (
    {
      className,
      variant = 'secondary',
      size = 'md',
      loading = false,
      disabled,
      children,
      ...props
    },
    ref
  ) => {
    const isPrimary = variant === 'primary';
    const isDanger = variant === 'danger';
    const isSecondary = variant === 'secondary';
    const isGhost = variant === 'ghost';

    return (
      <button
        ref={ref}
        disabled={loading || disabled}
        className={cn(
          // Base styles
          'inline-flex items-center justify-center font-semibold transition-all duration-200 select-none',
          'focus-visible:neu-focus focus:outline-none',
          'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none',
          
          // Sizing
          {
            'h-9 px-3 text-xs rounded-neu-sm': size === 'sm',
            'h-11 px-4 text-sm rounded-neu-md': size === 'md',
            'h-14 px-6 text-base rounded-neu-lg': size === 'lg',
            'h-11 w-11 rounded-neu-md p-0': size === 'icon',
          },

          // Variants
          {
            // Primary uses accent color with flat look but pressed state on active
            'bg-neu-accent-fill text-neu-accent-on hover:bg-neu-accent-fill/90 active:neu-pressed active:translate-y-px':
              isPrimary,
            
            // Secondary uses standard neumorphic raised effect
            'bg-neu-surface text-neu-text neu-raised-md hover:neu-raised-lg active:neu-pressed active:translate-y-px':
              isSecondary,
              
            // Danger uses red accent
            'bg-status-failed-fill text-status-failed-on hover:bg-status-failed-fill/90 active:neu-pressed active:translate-y-px':
              isDanger,

            // Ghost is flat and transparent until hover
            'bg-transparent text-neu-text hover:bg-neu-bg hover:neu-flat active:neu-pressed':
              isGhost,
          },
          className
        )}
        {...props}
      >
        {loading ? (
          <svg className="animate-spin -ml-1 mr-2 h-4 w-4 opacity-75" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        ) : null}
        {children}
      </button>
    );
  }
);
NeuButton.displayName = 'NeuButton';
