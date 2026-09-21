import React, { forwardRef, InputHTMLAttributes, TextareaHTMLAttributes, SelectHTMLAttributes, useState } from 'react';
import { Search, UploadCloud, Check } from 'lucide-react';
import { cn } from './utils';

// Textarea
export interface NeuTextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  error?: boolean;
}
export const NeuTextarea = forwardRef<HTMLTextAreaElement, NeuTextareaProps>(
  ({ className, error, ...props }, ref) => (
    <textarea
      ref={ref}
      className={cn(
        'flex w-full rounded-neu-md bg-neu-surface px-4 py-3 text-sm text-neu-text transition-colors',
        'neu-pressed placeholder:text-neu-muted',
        'focus-visible:neu-focus',
        'disabled:cursor-not-allowed disabled:opacity-50',
        error && 'border border-status-failed-fill outline-none ring-1 ring-status-failed-fill',
        className
      )}
      {...props}
    />
  )
);
NeuTextarea.displayName = 'NeuTextarea';

// Select
export interface NeuSelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  error?: boolean;
}
export const NeuSelect = forwardRef<HTMLSelectElement, NeuSelectProps>(
  ({ className, error, children, ...props }, ref) => (
    <select
      ref={ref}
      className={cn(
        'flex h-11 w-full appearance-none rounded-neu-md bg-neu-surface px-4 py-2 text-sm text-neu-text transition-colors',
        'neu-pressed placeholder:text-neu-muted focus-visible:neu-focus',
        'disabled:cursor-not-allowed disabled:opacity-50',
        error && 'border border-status-failed-fill outline-none ring-1 ring-status-failed-fill',
        className
      )}
      {...props}
    >
      {children}
    </select>
  )
);
NeuSelect.displayName = 'NeuSelect';

// Search
export const NeuSearch = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
  ({ className, ...props }, ref) => (
    <div className="relative">
      <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-neu-muted" />
      <input
        ref={ref}
        type="search"
        className={cn(
          'flex h-11 w-full rounded-neu-md bg-neu-surface pl-10 pr-4 py-2 text-sm text-neu-text transition-colors',
          'neu-pressed placeholder:text-neu-muted focus-visible:neu-focus',
          className
        )}
        {...props}
      />
    </div>
  )
);
NeuSearch.displayName = 'NeuSearch';

// Checkbox
export const NeuCheckbox = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
  ({ className, ...props }, ref) => (
    <div className="relative flex items-center justify-center h-5 w-5">
      <input
        type="checkbox"
        ref={ref}
        className={cn(
          'peer appearance-none h-5 w-5 rounded-neu-sm bg-neu-surface transition-all',
          'neu-pressed focus-visible:neu-focus cursor-pointer',
          'checked:bg-neu-accent-fill checked:neu-flat',
          className
        )}
        {...props}
      />
      <Check className="absolute pointer-events-none h-3.5 w-3.5 text-neu-accent-on opacity-0 peer-checked:opacity-100 transition-opacity" />
    </div>
  )
);
NeuCheckbox.displayName = 'NeuCheckbox';

// Toggle
export const NeuToggle = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(
  ({ className, ...props }, ref) => (
    <label className="relative inline-flex items-center cursor-pointer">
      <input type="checkbox" ref={ref} className="sr-only peer" {...props} />
      <div className={cn(
        "w-11 h-6 bg-neu-surface rounded-neu-pill neu-pressed peer-focus-visible:neu-focus transition-colors",
        "peer-checked:bg-neu-accent-fill"
      )}></div>
      <div className={cn(
        "absolute left-[2px] top-[2px] bg-white border border-slate-200 h-5 w-5 rounded-full transition-transform",
        "shadow-sm peer-checked:translate-x-full peer-checked:border-transparent"
      )}></div>
    </label>
  )
);
NeuToggle.displayName = 'NeuToggle';

// FileDropzone
export interface NeuFileDropzoneProps {
  onDrop?: (file: File) => void;
  accept?: string;
  error?: string;
}
export function NeuFileDropzone({ onDrop, accept, error }: NeuFileDropzoneProps) {
  const [isDragOver, setIsDragOver] = useState(false);

  return (
    <div
      onDragOver={(e) => { e.preventDefault(); setIsDragOver(true); }}
      onDragLeave={() => setIsDragOver(false)}
      onDrop={(e) => {
        e.preventDefault();
        setIsDragOver(false);
        if (e.dataTransfer.files?.[0] && onDrop) onDrop(e.dataTransfer.files[0]);
      }}
      className={cn(
        'relative flex flex-col items-center justify-center p-12 text-center rounded-neu-lg transition-all duration-200 border-2 border-transparent',
        isDragOver ? 'neu-pressed border-neu-accent-fill' : 'neu-raised-md',
        error ? 'border-status-failed-fill neu-pressed' : ''
      )}
    >
      <div className={cn("p-4 rounded-full mb-4", isDragOver ? "bg-neu-accent-fill text-neu-accent-on" : "bg-neu-surface neu-raised-sm text-neu-muted")}>
        <UploadCloud className="h-8 w-8" />
      </div>
      <h3 className="font-semibold text-neu-text text-lg">Drop your paper here</h3>
      <p className="text-sm text-neu-muted mt-2">or click to browse files</p>
      
      <input 
        type="file" 
        accept={accept} 
        onChange={(e) => e.target.files?.[0] && onDrop?.(e.target.files[0])}
        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer" 
        title="Upload file"
      />
      {error && <p className="mt-4 text-sm text-status-failed-text font-medium">{error}</p>}
    </div>
  );
}
