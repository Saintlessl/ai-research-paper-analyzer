import { useEffect, useState } from 'react';
import { Moon, Sun, Monitor } from 'lucide-react';
import { NeuButton } from './NeuButton';

type Theme = 'light' | 'dark' | 'system';

export function ThemeToggle() {
  const [theme, setTheme] = useState<Theme>('system');

  useEffect(() => {
    // Read current theme from localStorage on mount
    const stored = localStorage.getItem('theme') as Theme | null;
    if (stored) {
      setTheme(stored);
    }
  }, []);

  const applyTheme = (newTheme: Theme, e?: React.MouseEvent) => {
    setTheme(newTheme);
    
    const updateDOM = () => {
        const isDark = newTheme === 'dark' || (newTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', isDark);
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', isDark ? '#111418' : '#E8ECF3');
        
        if (newTheme === 'system') {
            localStorage.removeItem('theme');
        } else {
            localStorage.setItem('theme', newTheme);
        }
    };

    // Modern View Transitions API for a slick theme switch
    if (!document.startViewTransition || !e) {
      updateDOM();
      return;
    }

    const x = e.clientX;
    const y = e.clientY;
    const endRadius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

    const transition = document.startViewTransition(() => {
      updateDOM();
    });

    transition.ready.then(() => {
      const isDark = document.documentElement.classList.contains('dark');
      document.documentElement.animate(
        {
          clipPath: [
            `circle(0px at ${x}px ${y}px)`,
            `circle(${endRadius}px at ${x}px ${y}px)`,
          ],
        },
        {
          duration: 500,
          easing: 'ease-out',
          pseudoElement: isDark ? '::view-transition-new(root)' : '::view-transition-old(root)',
        }
      );
    });
  };

  const cycleTheme = (e: React.MouseEvent) => {
    const next: Record<Theme, Theme> = {
      light: 'dark',
      dark: 'system',
      system: 'light'
    };
    applyTheme(next[theme], e);
  };

  return (
    <NeuButton 
      variant="ghost" 
      size="icon" 
      onClick={cycleTheme} 
      title={`Theme: ${theme}`}
      aria-label={`Toggle theme (current: ${theme})`}
      className="relative overflow-hidden group"
    >
      <div className="absolute inset-0 bg-neu-accent-fill/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-neu-pill" />
      <span className="relative z-10 flex items-center justify-center">
          {theme === 'light' && <Sun className="h-5 w-5 text-amber-500" />}
          {theme === 'dark' && <Moon className="h-5 w-5 text-neu-accent-text" />}
          {theme === 'system' && <Monitor className="h-5 w-5 text-neu-muted" />}
      </span>
    </NeuButton>
  );
}
