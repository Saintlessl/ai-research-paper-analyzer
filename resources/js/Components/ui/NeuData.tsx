import React from 'react';
import { cn } from './utils';
import { FileText, AlertCircle, AlertTriangle, Info, CheckCircle2, XCircle, Clock, Archive } from 'lucide-react';
import { NeuCard } from './NeuCard';

// Badge (Generic)
export function NeuBadge({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <span className={cn("inline-flex items-center px-2.5 py-0.5 rounded-neu-pill text-xs font-semibold neu-raised-sm bg-neu-surface text-neu-text", className)}>
      {children}
    </span>
  );
}

// Severity Badge
export type SeverityLevel = 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL';
export function NeuSeverityBadge({ level }: { level: SeverityLevel }) {
  const config = {
    LOW: { color: 'bg-severity-low-fill text-severity-low-on', icon: Info },
    MEDIUM: { color: 'bg-severity-medium-fill text-severity-medium-on', icon: AlertCircle },
    HIGH: { color: 'bg-severity-high-fill text-severity-high-on', icon: AlertTriangle },
    CRITICAL: { color: 'bg-severity-critical-fill text-severity-critical-on', icon: XCircle },
  }[level] || { color: 'bg-neu-surface text-neu-muted', icon: Info };

  const Icon = config.icon;

  return (
    <span className={cn("inline-flex items-center gap-1.5 px-2.5 py-1 rounded-neu-pill text-xs font-bold neu-pressed", config.color)}>
      <Icon className="h-3.5 w-3.5" />
      {level}
    </span>
  );
}

// Status Pill
export type StatusType = 'UPLOADED' | 'PROCESSING' | 'ANALYZED' | 'FAILED' | 'ARCHIVED';
export function NeuStatusPill({ status }: { status: StatusType }) {
  const config = {
    UPLOADED: { color: 'text-status-uploaded-text', icon: FileText },
    PROCESSING: { color: 'text-status-processing-text', icon: Clock, animate: true },
    ANALYZED: { color: 'text-status-analyzed-text', icon: CheckCircle2 },
    FAILED: { color: 'text-status-failed-text', icon: XCircle },
    ARCHIVED: { color: 'text-status-uploaded-text text-opacity-70', icon: Archive },
  }[status] || { color: 'text-neu-muted', icon: Info };

  const Icon = config.icon;

  return (
    <span className={cn("inline-flex items-center gap-1.5 px-3 py-1.5 rounded-neu-pill text-xs font-bold neu-raised-sm bg-neu-surface", config.color)}>
      <Icon className={cn("h-4 w-4", config.animate && "animate-spin-slow")} />
      {status}
    </span>
  );
}

// Avatar
export function NeuAvatar({ name, src, size = 'md' }: { name: string; src?: string; size?: 'sm' | 'md' | 'lg' }) {
  const initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
  const sz = { sm: 'h-8 w-8 text-xs', md: 'h-10 w-10 text-sm', lg: 'h-14 w-14 text-base' }[size];
  
  return (
    <div className={cn("relative inline-flex items-center justify-center rounded-full bg-neu-surface neu-raised-sm flex-shrink-0", sz)}>
      {src ? (
        <img src={src} alt={name} className="h-full w-full rounded-full object-cover p-[2px]" />
      ) : (
        <span className="font-bold text-neu-muted">{initials}</span>
      )}
    </div>
  );
}

// Score Gauge (Radial)
export function ScoreGauge({ score, label }: { score: number; label?: string }) {
  // Normalize 0-100
  const val = Math.max(0, Math.min(100, score));
  const radius = 38;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (val / 100) * circumference;

  return (
    <div className="flex flex-col items-center">
      <div className="relative w-24 h-24 flex items-center justify-center rounded-full neu-pressed bg-neu-surface">
        <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
          <circle
            className="text-neu-bg transition-all duration-[600ms] ease-[cubic-bezier(0.4,0,0.2,1)]"
            strokeWidth="8"
            stroke="currentColor"
            fill="transparent"
            r={radius}
            cx="50"
            cy="50"
          />
          <circle
            className="text-neu-accent-fill transition-all duration-[600ms] ease-[cubic-bezier(0.4,0,0.2,1)]"
            strokeWidth="8"
            strokeLinecap="round"
            stroke="currentColor"
            fill="transparent"
            r={radius}
            cx="50"
            cy="50"
            style={{ strokeDasharray: circumference, strokeDashoffset }}
          />
        </svg>
        <div className="absolute inset-0 flex items-center justify-center">
          <span className="text-2xl font-bold text-neu-text tabular-nums">{val}</span>
        </div>
      </div>
      {label && <span className="mt-3 text-sm font-semibold text-neu-muted uppercase tracking-wider">{label}</span>}
    </div>
  );
}

// Score Bar (Linear)
export function ScoreBar({ score, label }: { score: number; label: string }) {
  const val = Math.max(0, Math.min(100, score));
  return (
    <div className="w-full">
      <div className="flex justify-between items-end mb-2">
        <span className="text-sm font-semibold text-neu-text">{label}</span>
        <span className="text-sm font-bold text-neu-accent-text tabular-nums">{val}/100</span>
      </div>
      <div className="h-3 w-full bg-neu-surface neu-pressed rounded-neu-pill overflow-hidden">
        <div 
          className="h-full bg-neu-accent-fill rounded-neu-pill transition-all duration-[600ms] ease-[cubic-bezier(0.4,0,0.2,1)]"
          style={{ width: `${val}%` }}
        />
      </div>
    </div>
  );
}

// Table
export function NeuTable({ headers, children }: { headers: string[], children: React.ReactNode }) {
  return (
    <NeuCard padding="none" elevation="raised-md" className="overflow-x-auto">
      <table className="w-full text-left text-sm whitespace-nowrap">
        <thead>
          <tr className="border-b border-neu-hairline">
            {headers.map((h, i) => (
              <th key={i} className="px-6 py-4 font-semibold text-neu-muted uppercase tracking-wider text-xs">
                {h}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-neu-hairline">
          {children}
        </tbody>
      </table>
    </NeuCard>
  );
}

export function NeuTableRow({ children, className }: { children: React.ReactNode, className?: string }) {
  return (
    <tr className={cn("hover:bg-white/5 transition-colors neu-flat", className)}>
      {children}
    </tr>
  );
}
