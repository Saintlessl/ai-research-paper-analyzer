import { ButtonHTMLAttributes } from 'react';
import { NeuButton } from '@/Components/ui/NeuButton';

/**
 * @deprecated Use NeuButton with variant="secondary" instead.
 */
export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <NeuButton variant="secondary" type={type} className={className} disabled={disabled} {...props}>
            {children}
        </NeuButton>
    );
}
