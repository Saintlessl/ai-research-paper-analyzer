import { InputHTMLAttributes, forwardRef } from 'react';
import { cn } from './utils';

export interface NeuInputProps extends InputHTMLAttributes<HTMLInputElement> {
  error?: boolean;
}

export const NeuInput = forwardRef<HTMLInputElement, NeuInputProps>(
  ({ className, error, ...props }, ref) => {
    return (
      <input
        ref={ref}
        className={cn(
          'flex h-11 w-full rounded-neu-md bg-neu-surface px-4 py-2 text-sm text-neu-text transition-colors',
          'neu-pressed placeholder:text-neu-muted',
          'focus-visible:neu-focus',
          'disabled:cursor-not-allowed disabled:opacity-50',
          error && 'border border-status-failed outline-none ring-1 ring-status-failed',
          className
        )}
        {...props}
      />
    );
  }
);
NeuInput.displayName = 'NeuInput';
