import React from 'react';
import { cn } from './utils';
import { NeuButton, NeuButtonProps } from './NeuButton';
import { ChevronLeft, ChevronRight } from 'lucide-react';

// Tabs
export interface NeuTabsProps {
  tabs: { id: string; label: string }[];
  activeId: string;
  onChange?: (id: string) => void;
  className?: string;
}
export function NeuTabs({ tabs, activeId, onChange, className }: NeuTabsProps) {
  return (
    <div className={cn("inline-flex bg-neu-surface p-1.5 rounded-neu-lg neu-pressed", className)}>
      {tabs.map((tab) => {
        const isActive = tab.id === activeId;
        return (
          <button
            key={tab.id}
            onClick={() => onChange?.(tab.id)}
            className={cn(
              "relative px-4 py-2 text-sm font-semibold transition-all duration-200 rounded-neu-md focus-visible:neu-focus outline-none",
              isActive ? "text-neu-accent-text bg-neu-accent neu-flat" : "text-neu-text-muted hover:text-neu-text hover:bg-white/5"
            )}
          >
            {tab.label}
          </button>
        );
      })}
    </div>
  );
}

// IconButton
export const NeuIconButton = React.forwardRef<HTMLButtonElement, NeuButtonProps & { icon: React.ReactNode }>(
  ({ icon, ...props }, ref) => {
    return (
      <NeuButton ref={ref} size="icon" {...props}>
        {icon}
      </NeuButton>
    );
  }
);
NeuIconButton.displayName = 'NeuIconButton';

// Pagination
export interface NeuPaginationProps {
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  className?: string;
}
export function NeuPagination({ currentPage, totalPages, onPageChange, className }: NeuPaginationProps) {
  return (
    <div className={cn("flex items-center gap-2", className)}>
      <NeuIconButton 
        icon={<ChevronLeft className="h-4 w-4" />} 
        aria-label="Previous page" 
        disabled={currentPage <= 1}
        onClick={() => onPageChange(currentPage - 1)}
      />
      <span className="text-sm font-medium text-neu-muted px-2">
        Page {currentPage} of {totalPages}
      </span>
      <NeuIconButton 
        icon={<ChevronRight className="h-4 w-4" />} 
        aria-label="Next page" 
        disabled={currentPage >= totalPages}
        onClick={() => onPageChange(currentPage + 1)}
      />
    </div>
  );
}

// Dropdown (Basic wrapper)
export interface NeuDropdownProps {
  trigger: React.ReactNode;
  children: React.ReactNode;
  isOpen: boolean;
  setIsOpen: (v: boolean) => void;
}
export function NeuDropdown({ trigger, children, isOpen, setIsOpen }: NeuDropdownProps) {
  return (
    <div className="relative inline-block text-left">
      <div onClick={() => setIsOpen(!isOpen)}>{trigger}</div>
      {isOpen && (
        <>
          <div className="fixed inset-0 z-30" onClick={() => setIsOpen(false)}></div>
          <div className="absolute right-0 z-dropdown mt-2 w-56 rounded-neu-md bg-neu-surface neu-raised-lg ring-1 ring-neu-hairline py-1">
            {children}
          </div>
        </>
      )}
    </div>
  );
}
