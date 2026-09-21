import { ButtonHTMLAttributes } from 'react';
import { NeuButton } from '@/Components/ui/NeuButton';

/**
 * @deprecated Use NeuButton with variant="danger" instead.
 */
export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <NeuButton variant="danger" className={className} disabled={disabled} {...props}>
            {children}
        </NeuButton>
    );
}
