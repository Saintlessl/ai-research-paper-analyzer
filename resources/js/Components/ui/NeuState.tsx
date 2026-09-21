import React from 'react';
import { cn } from './utils';
import { NeuCard } from './NeuCard';
import { FileText, AlertTriangle } from 'lucide-react';
import { NeuButton } from './NeuButton';

// Skeleton
export function NeuSkeleton({ className, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div 
      className={cn("animate-pulse bg-neu-pressed bg-neu-surface rounded-neu-sm", className)} 
      {...props} 
    />
  );
}

// Empty State
export interface NeuEmptyStateProps {
  title: string;
  description: string;
  action?: React.ReactNode;
  icon?: React.ReactNode;
}
export function NeuEmptyState({ title, description, action, icon }: NeuEmptyStateProps) {
  return (
    <NeuCard elevation="flat" padding="lg" className="flex flex-col items-center justify-center text-center border-dashed">
      <div className="p-4 rounded-full bg-neu-surface neu-pressed text-neu-muted mb-4">
        {icon || <FileText className="h-8 w-8" />}
      </div>
      <h3 className="text-lg font-semibold">{title}</h3>
      <p className="mt-2 text-sm text-neu-muted max-w-sm mb-6">{description}</p>
      {action}
    </NeuCard>
  );
}

// Error State
export interface NeuErrorStateProps {
  title?: string;
  message: string;
  onRetry?: () => void;
}
export function NeuErrorState({ title = "Something went wrong", message, onRetry }: NeuErrorStateProps) {
  return (
    <NeuCard elevation="raised-md" padding="lg" className="flex flex-col items-center justify-center text-center">
      <div className="p-4 rounded-full bg-status-failed-fill/10 text-status-failed-text mb-4">
        <AlertTriangle className="h-8 w-8" />
      </div>
      <h3 className="text-lg font-bold text-status-failed-text">{title}</h3>
      <p className="mt-2 text-sm text-neu-muted max-w-md mb-6">{message}</p>
      {onRetry && (
        <NeuButton onClick={onRetry} variant="secondary">
          Try Again
        </NeuButton>
      )}
    </NeuCard>
  );
}

// Progress Bar
export function NeuProgressBar({ value, label }: { value: number; label?: string }) {
  const safeValue = Math.max(0, Math.min(100, value));
  return (
    <div className="w-full">
      {label && (
        <div className="flex justify-between text-xs font-semibold mb-2">
          <span>{label}</span>
          <span className="tabular-nums">{safeValue}%</span>
        </div>
      )}
      <div className="h-2 w-full bg-neu-surface neu-pressed rounded-neu-pill overflow-hidden">
        <div 
          className="h-full bg-neu-accent-fill transition-all duration-300 ease-out"
          style={{ width: `${safeValue}%` }}
        />
      </div>
    </div>
  );
}
