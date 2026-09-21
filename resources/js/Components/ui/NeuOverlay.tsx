import React, { useEffect, useRef } from 'react';
import { X } from 'lucide-react';
import { cn } from './utils';
import { NeuIconButton } from './NeuNavigation';
import { NeuCard } from './NeuCard';

// Modal
export interface NeuModalProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
}
export function NeuModal({ isOpen, onClose, title, children, footer }: NeuModalProps) {
  const modalRef = useRef<HTMLDivElement>(null);
  const previousFocusRef = useRef<HTMLElement | null>(null);

  useEffect(() => {
    if (isOpen) {
      previousFocusRef.current = document.activeElement as HTMLElement;
      document.body.style.overflow = 'hidden';

      const handleKeyDown = (e: KeyboardEvent) => {
        if (e.key === 'Escape') onClose();
        if (e.key === 'Tab' && modalRef.current) {
          const focusable = modalRef.current.querySelectorAll<HTMLElement>(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
          );
          if (focusable.length === 0) return;
          const first = focusable[0];
          const last = focusable[focusable.length - 1];

          if (e.shiftKey) {
            if (document.activeElement === first) {
              e.preventDefault();
              last.focus();
            }
          } else {
            if (document.activeElement === last) {
              e.preventDefault();
              first.focus();
            }
          }
        }
      };

      window.addEventListener('keydown', handleKeyDown);

      // Focus first element
      setTimeout(() => {
        const focusable = modalRef.current?.querySelectorAll<HTMLElement>(
          'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        if (focusable && focusable.length > 0) {
          focusable[0].focus();
        }
      }, 10);

      return () => {
        document.body.style.overflow = '';
        window.removeEventListener('keydown', handleKeyDown);
        if (previousFocusRef.current) previousFocusRef.current.focus();
      };
    }
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-modal flex items-center justify-center p-4">
      {/* Backdrop */}
      <div 
        className="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"
        onClick={onClose}
        aria-hidden="true"
      />
      
      {/* Dialog */}
      <div 
        ref={modalRef}
        className="relative z-10 w-full max-w-lg transform transition-all"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
      >
        <NeuCard elevation="raised-lg" padding="none" className="overflow-hidden flex flex-col max-h-[90vh]">
          <div className="flex items-center justify-between px-6 py-4 border-b border-neu-hairline">
            <h2 id="modal-title" className="text-lg font-bold">{title}</h2>
            <NeuIconButton icon={<X className="h-5 w-5" />} onClick={onClose} variant="ghost" aria-label="Close modal" />
          </div>
          
          <div className="p-6 overflow-y-auto">
            {children}
          </div>

          {footer && (
            <div className="px-6 py-4 border-t border-neu-hairline bg-neu-bg/30 flex justify-end gap-3">
              {footer}
            </div>
          )}
        </NeuCard>
      </div>
    </div>
  );
}

// Drawer
export interface NeuDrawerProps {
  isOpen: boolean;
  onClose: () => void;
  title: string;
  children: React.ReactNode;
  side?: 'left' | 'right';
}
export function NeuDrawer({ isOpen, onClose, title, children, side = 'right' }: NeuDrawerProps) {
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
      const handleEsc = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
      window.addEventListener('keydown', handleEsc);
      return () => {
        document.body.style.overflow = '';
        window.removeEventListener('keydown', handleEsc);
      };
    }
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-drawer flex">
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />
      <div 
        className={cn(
          "relative z-10 w-full max-w-md h-full bg-neu-surface neu-raised-lg flex flex-col transition-transform",
          side === 'right' ? "ml-auto" : "mr-auto"
        )}
      >
        <div className="flex items-center justify-between px-6 py-4 border-b border-neu-hairline">
          <h2 className="text-lg font-bold">{title}</h2>
          <NeuIconButton icon={<X className="h-5 w-5" />} onClick={onClose} variant="ghost" aria-label="Close drawer" />
        </div>
        <div className="flex-1 overflow-y-auto p-6">
          {children}
        </div>
      </div>
    </div>
  );
}

// Tooltip (Simple wrapper using group-hover)
export function NeuTooltip({ content, children, position = 'top' }: { content: string; children: React.ReactNode; position?: 'top' | 'bottom' }) {
  return (
    <div className="group relative inline-flex">
      {children}
      <div className={cn(
        "absolute z-tooltip hidden group-hover:block w-max max-w-xs px-3 py-2 text-xs font-medium text-neu-text bg-neu-surface neu-raised-md border border-neu-hairline rounded-neu-sm",
        position === 'top' ? "bottom-full left-1/2 -translate-x-1/2 mb-2" : "top-full left-1/2 -translate-x-1/2 mt-2"
      )}>
        {content}
      </div>
    </div>
  );
}

// Toast (Static presentation component)
export function NeuToast({ title, message, type = 'info', onClose }: { title: string; message: string; type?: 'success' | 'error' | 'info'; onClose?: () => void }) {
  return (
    <div className="flex z-toast w-full max-w-sm overflow-hidden bg-neu-surface neu-raised-lg border border-neu-hairline rounded-neu-md">
      <div className={cn("w-1.5", type === 'success' ? 'bg-status-analyzed-fill' : type === 'error' ? 'bg-status-failed-fill' : 'bg-neu-accent-fill')} />
      <div className="flex items-center justify-between w-full p-4">
        <div>
          <p className="font-semibold text-sm">{title}</p>
          <p className="text-xs text-neu-muted mt-1">{message}</p>
        </div>
        {onClose && <NeuIconButton icon={<X className="h-4 w-4" />} size="sm" variant="ghost" onClick={onClose} aria-label="Close notification" />}
      </div>
    </div>
  );
}
