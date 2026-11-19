// Merge into the `theme.extend` section of tailwind.config.ts in the Lovable project.
// Adds the Navy-Trust semantic colours on top of shadcn's defaults.
export const kwvTijExtend = {
  fontFamily: {
    sans: ['"Noto Sans"', '"Noto Sans Lao"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
  },
  colors: {
    'primary-soft': 'hsl(var(--primary-soft))',
    success: { DEFAULT: 'hsl(var(--success))', bg: 'hsl(var(--success-bg))' },
    warning: { DEFAULT: 'hsl(var(--warning))', bg: 'hsl(var(--warning-bg))' },
    'destructive-bg': 'hsl(var(--destructive-bg))',
    info: { DEFAULT: 'hsl(var(--info))', bg: 'hsl(var(--info-bg))' },
    chart: { 1: 'hsl(var(--chart-1))', 2: 'hsl(var(--chart-2))' },
    heritage: 'hsl(var(--heritage))',
    'brand-navy': 'hsl(var(--brand-navy))',
    avatar: {
      male: 'hsl(var(--avatar-male))', 'male-foreground': 'hsl(var(--avatar-male-foreground))',
      female: 'hsl(var(--avatar-female))', 'female-foreground': 'hsl(var(--avatar-female-foreground))',
    },
  },
  borderRadius: { lg: 'var(--radius)', md: 'calc(var(--radius) - 2px)', sm: 'calc(var(--radius) - 4px)', xl: '0.75rem', '2xl': '1rem' },
  minHeight: { touch: '2.75rem' }, // 44px minimum for every button / control
};
