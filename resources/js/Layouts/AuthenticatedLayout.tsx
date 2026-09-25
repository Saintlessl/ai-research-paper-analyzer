import React, { PropsWithChildren, ReactNode, useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { LayoutDashboard, BookOpen, Upload, Users, ClipboardList, Activity, ChevronLeft, ChevronRight, UserRound, LogOut, Shield } from 'lucide-react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { AuthenticatedPageProps } from '@/types';
import { normalizeRole } from '@/lib/contracts';
import { cn } from '@/Components/ui/utils';
import { NeuAvatar } from '@/Components/ui/NeuData';
import { NeuSearch } from '@/Components/ui/NeuForm';
import { NeuDropdown, NeuIconButton } from '@/Components/ui/NeuNavigation';
import { ThemeToggle } from '@/Components/ui/ThemeToggle';
import { NeuCard } from '@/Components/ui/NeuCard';

const activePath = (href: string) => window.location.pathname === href || (href !== '/dashboard' && window.location.pathname.startsWith(`${href}/`));

export default function Authenticated({ header, children }: PropsWithChildren<{ header?: ReactNode }>) {
  const { auth, flash } = usePage<AuthenticatedPageProps>().props;
  const user = auth.user;
  const role = normalizeRole(user);
  
  const [isCollapsed, setIsCollapsed] = useState(false);
  const [isMobile, setIsMobile] = useState(false);
  
  // Load sidebar state
  useEffect(() => {
    const saved = localStorage.getItem('sidebar-collapsed');
    if (saved) setIsCollapsed(saved === 'true');
    
    const checkMobile = () => setIsMobile(window.innerWidth < 768);
    checkMobile();
    window.addEventListener('resize', checkMobile);
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  const toggleSidebar = () => {
    const next = !isCollapsed;
    setIsCollapsed(next);
    localStorage.setItem('sidebar-collapsed', String(next));
  };

  const dashboardHref = (role === 'admin' || role === 'super_admin') ? '/admin/dashboard' : '/dashboard';

  const menuItems = [
    { label: 'Dashboard', href: dashboardHref, icon: LayoutDashboard, roles: ['super_admin', 'admin', 'researcher', 'reviewer'] },
    { label: 'Papers', href: '/papers', icon: BookOpen, roles: ['super_admin', 'admin', 'researcher'] },
    { label: 'Upload Paper', href: '/papers/create', icon: Upload, roles: ['researcher'] },
    { label: 'Assigned Reviews', href: '/reviews', icon: ClipboardList, roles: ['reviewer'] },
    { label: 'Users', href: '/admin/users', icon: Users, roles: ['super_admin', 'admin'] },
    { label: 'Roles', href: '/admin/roles-permissions', icon: Shield, roles: ['super_admin'] },
  ].filter(item => item.roles.includes(role as string));

  const [userMenuOpen, setUserMenuOpen] = useState(false);

  return (
    <div className="min-h-screen bg-neu-bg text-neu-text transition-colors">
      <a href="#main-content" className="fixed left-3 top-3 z-[60] -translate-y-20 rounded-neu-md bg-neu-surface px-4 py-2 text-sm font-semibold shadow focus:translate-y-0 focus:ring-2 focus:ring-neu-accent-fill">
        Skip to content
      </a>

      {/* Topbar */}
      <header className={cn(
        "fixed top-0 right-0 z-40 flex h-16 items-center justify-between border-b border-neu-hairline bg-neu-bg/80 px-4 backdrop-blur-md transition-all",
        !isMobile ? (isCollapsed ? "left-[80px]" : "left-[260px]") : "left-0"
      )}>
        <div className="flex items-center gap-4 flex-1">
          {/* Global Search */}
          <div className="hidden sm:block w-full max-w-md">
            <NeuSearch placeholder="Search papers, authors..." />
          </div>
        </div>
        
        <div className="flex items-center gap-3">
          {/* AI Jobs Indicator */}
          <button className="relative flex items-center justify-center w-10 h-10 rounded-full bg-neu-surface neu-raised-sm text-neu-muted hover:text-neu-accent-text transition-colors" title="Active AI Jobs">
            <Activity className="h-5 w-5" />
            <span className="absolute top-0 right-0 flex h-4 w-4 items-center justify-center rounded-full bg-neu-accent-fill text-[9px] font-bold text-neu-accent-on">3</span>
          </button>
          
          <ThemeToggle />
          
          {/* User Profile */}
          <NeuDropdown
            isOpen={userMenuOpen}
            setIsOpen={setUserMenuOpen}
            trigger={
              <button className="flex items-center gap-2 rounded-full hover:opacity-80 transition-opacity">
                <NeuAvatar name={user.name} size="md" />
              </button>
            }
          >
            <div className="px-4 py-3 border-b border-neu-hairline">
              <p className="text-sm font-bold truncate">{user.name}</p>
              <p className="text-xs text-neu-muted truncate">{user.email}</p>
              <p className="text-[10px] uppercase font-bold text-neu-accent-text mt-1 tracking-wider">{role}</p>
            </div>
            <div className="p-1">
                {auth.is_actual_super_admin && !auth.impersonating && (
                    <div className="px-3 py-2 border-b border-neu-hairline mb-1 bg-amber-500/10 rounded-sm">
                        <label className="text-[10px] uppercase font-bold text-amber-500 mb-1 flex justify-between">
                            <span>Testing Mode</span>
                            <Shield className="w-3 h-3" />
                        </label>
                        <select 
                            className="w-full text-xs font-bold px-2 py-1.5 rounded bg-neu-surface border border-neu-border text-neu-text cursor-pointer focus:ring-0 focus:outline-none"
                            value={auth.active_role_override || 'super_admin'}
                            onChange={(e) => {
                                setUserMenuOpen(false);
                                router.post('/admin/switch-role', { role: e.target.value });
                            }}
                        >
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="reviewer">Reviewer</option>
                            <option value="researcher">Researcher</option>
                        </select>
                    </div>
                )}
              <Link href="/profile" className="flex items-center gap-2 rounded-neu-sm px-3 py-2 text-sm hover:bg-neu-surface hover:text-neu-accent-text transition-colors">
                <UserRound className="h-4 w-4" /> Profile
              </Link>
              <Link href="/logout" method="post" as="button" className="flex w-full items-center gap-2 rounded-neu-sm px-3 py-2 text-sm text-status-failed-text hover:bg-status-failed-fill/10 transition-colors">
                <LogOut className="h-4 w-4" /> Log out
              </Link>
            </div>
          </NeuDropdown>
        </div>
      </header>

      {/* Desktop Sidebar */}
      {!isMobile && (
        <aside className={cn(
          "fixed inset-y-0 left-0 z-50 flex flex-col border-r border-neu-hairline bg-neu-surface transition-all duration-300",
          isCollapsed ? "w-[80px]" : "w-[260px]"
        )}>
          <div className="flex h-16 items-center justify-between px-4 border-b border-transparent">
            <Link href="/dashboard" className="flex items-center gap-3 overflow-hidden whitespace-nowrap pt-1 pl-1">
              <ApplicationLogo className="h-8 w-8 text-neu-accent-fill shrink-0" />
              {!isCollapsed && <span className="font-bold tracking-tight text-lg">ScholarLens</span>}
            </Link>
          </div>
          
          <nav className="flex-1 space-y-2 p-3 overflow-y-auto mt-4">
            {menuItems.map(({ label, href, icon: Icon }) => {
              const active = activePath(href);
              return (
                <Link
                  key={href}
                  href={href}
                  title={isCollapsed ? label : undefined}
                  className={cn(
                    "relative flex items-center gap-3 rounded-neu-md px-3 py-3 transition-all",
                    active 
                      ? "neu-pressed text-neu-accent-text font-bold" 
                      : "text-neu-muted hover:neu-raised-sm hover:text-neu-text",
                    isCollapsed && "justify-center px-0"
                  )}
                >
                  {active && <div className="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-neu-accent-fill rounded-r-full" />}
                  <Icon className={cn("shrink-0", isCollapsed ? "h-6 w-6" : "h-5 w-5")} />
                  {!isCollapsed && <span className="whitespace-nowrap">{label}</span>}
                </Link>
              );
            })}
          </nav>
          
          <div className="p-3 border-t border-neu-hairline flex justify-center">
            <NeuIconButton 
              icon={isCollapsed ? <ChevronRight className="h-4 w-4" /> : <ChevronLeft className="h-4 w-4" />} 
              onClick={toggleSidebar}
              variant="ghost"
              aria-label="Toggle Sidebar"
            />
          </div>
        </aside>
      )}

      {/* Mobile Bottom Navigation */}
      {isMobile && (
        <nav className="fixed bottom-0 left-0 right-0 z-50 flex h-16 items-center justify-around border-t border-neu-hairline bg-neu-surface/90 backdrop-blur-md pb-safe">
          {menuItems.map(({ label, href, icon: Icon }) => {
            const active = activePath(href);
            return (
              <Link
                key={href}
                href={href}
                className={cn(
                  "flex flex-col items-center justify-center w-full h-full gap-1",
                  active ? "text-neu-accent-text" : "text-neu-muted hover:text-neu-text"
                )}
              >
                <div className={cn("p-1 rounded-full transition-all", active && "neu-pressed bg-neu-bg")}>
                  <Icon className="h-5 w-5" />
                </div>
                <span className="text-[10px] font-medium truncate w-full text-center px-1">{label}</span>
              </Link>
            );
          })}
        </nav>
      )}

      {/* Main Content */}
      <div className={cn(
        "min-w-0 transition-all duration-300 flex flex-col min-h-screen pt-16",
        !isMobile ? (isCollapsed ? "pl-[80px]" : "pl-[260px]") : "pb-16"
      )}>
        {user && auth.impersonating && (
          <div className="bg-neu-primary text-neu-primary-text px-4 py-3 sm:px-6 lg:px-8 flex justify-between items-center text-sm font-bold border-b border-neu-border">
            <span>You are currently impersonating {user.name} ({user.role}).</span>
            <button 
              onClick={() => router.post('/leave-impersonation')} 
              className="px-3 py-1 bg-white/20 hover:bg-white/30 rounded-neu-sm transition-colors"
            >
              Leave Impersonation
            </button>
          </div>
        )}
        {flash?.success && (
          <div className="mx-4 mt-4 sm:mx-6 lg:mx-8">
            <NeuCard className="border-l-4 border-l-status-analyzed-fill bg-status-analyzed-fill/5" padding="sm" elevation="flat">
              <p className="text-sm text-status-analyzed-text font-medium">{flash.success}</p>
            </NeuCard>
          </div>
        )}
        {flash?.error && (
          <div className="mx-4 mt-4 sm:mx-6 lg:mx-8">
            <NeuCard className="border-l-4 border-l-status-failed-fill bg-status-failed-fill/5" padding="sm" elevation="flat">
              <p className="text-sm text-status-failed-text font-medium">{flash.error}</p>
            </NeuCard>
          </div>
        )}
        
        {header && <div className="border-b border-neu-hairline bg-neu-surface px-4 py-4 sm:px-6 lg:px-8">{header}</div>}
        
        <main id="main-content" className="flex-1 p-4 sm:p-6 lg:p-8">
          {children}
        </main>
      </div>
    </div>
  );
}

